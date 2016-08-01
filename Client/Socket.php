<?php
namespace Client;

require_once dirname(__FILE__).'/Connector.php';
require_once dirname(__DIR__).'/Exceptions/SocketInvalidUrlScheme.php';

use Evenement\EventEmitter;
use Exceptions\SocketInvalidUrlScheme;
use GuzzleHttp\Psr7\Uri;
use Protocol\DefaultTransport;
use Protocol\SocketConnection;
use Protocol\SocketTransport;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Zend\Log\LoggerInterface;

class Socket extends EventEmitter
{
    const STATE_CONNECTED = 1;
    const STATE_CLOSING = 2;
    const STATE_CLOSED = 3;

    protected $state = self::STATE_CLOSED;

    protected $url;

    /**
     * @var SocketConnection
     */
    protected $stream;
    protected $socket;


    protected $request;
    protected $response;

    /**
     * @var SocketTransport
     */
    protected $transport = null;

    protected $headers;
    protected $loop;

    protected $logger;

    protected $isClosing = false;

    protected $streamOptions = null;

    public function __construct($url, LoopInterface $loop, LoggerInterface $logger, array $streamOptions = null)
    {
        $this->logger = $logger;
        $this->loop = $loop;
        $this->streamOptions = $streamOptions;
        $url = new Uri($url);

        $this->url = $url;

        if ($url->getScheme() !== 'tcp') {
            throw new SocketInvalidUrlScheme();
        }

        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $this->dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
    }

    public function open($timeOut=null)
    {
        /**
         * @var $that self
         */
        $that = $this;

        $uri = $this->url;

        $connector = new Connector($this->loop, $this->dns, $this->streamOptions);

        $deferred = new Deferred();

        $connector->create($uri->getHost(), $uri->getPort())
            ->then(function (\React\Stream\DuplexStreamInterface $stream) use ($that, $uri, $deferred, $timeOut){
                if($timeOut){
                    $timeOutTimer = $that->loop->addTimer($timeOut, function() use($deferred, $stream, $that){
                        $stream->close();
                        $that->logger->notice("Timeout occured, closing connection");
                        $that->emit("error");
                        $deferred->reject("Timeout occured");
                    });
                } else $timeOutTimer = null;

                $transport = new DefaultTransport($stream);
                $transport->setLogger($that->logger);
                $that->transport = $transport;
                $that->stream = $stream;

                $stream->on("close", function() use($that){
                    $that->isClosing = false;
                    $that->state = Socket::STATE_CLOSED;
                    $that->emit('close');
                });

                // Give the chance to change request
//                $transport->on("request", function(Request $handshake) use($that){
//                    $that->emit("request", func_get_args());
//                });
//
//                $transport->on("handshake", function(Handshake $handshake) use($that){
//                    $that->request = $handshake->getRequest();
//                    $that->response = $handshake->getRequest();
//
//                    $that->emit("handshake", [$handshake]);
//                });

                $transport->on("connect", function() use(&$state, $that, $transport, $timeOutTimer, $deferred){
                    if($timeOutTimer)
                        $timeOutTimer->cancel();

                    $deferred->resolve($transport);
                    $that->state = Socket::STATE_CONNECTED;
                    $that->emit("connect");

                });

                $transport->on('message', function ($message) use ($that, $transport) {
                    $that->emit("message", ["message" => $message]);
                });

                $transport->emit('connect');
            }, function($reason) use ($that, $deferred)
            {
                $deferred->reject($reason);
                $that->logger->err($reason);
            });

        return $deferred->promise();

    }

    public function send($string)
    {
        $this->transport->sendString($string);
    }

    public function sendMessage( $msg)
    {
        $this->transport->sendMessage($msg);
    }

    public function close()
    {
        if ($this->isClosing)
            return;

        $this->isClosing = true;
        $this->state = self::STATE_CLOSING;
        $stream = $this->stream;

        $closeTimer = $this->loop->addTimer(5, function () use ($stream) {
            $stream->close();
        });

        $loop = $this->loop;
        $stream->once("close", function () use ($closeTimer, $loop) {
            if ($closeTimer)
                $loop->cancelTimer($closeTimer);
        });
    }
}
