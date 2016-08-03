<?php
namespace Server;
include_once dirname(__DIR__) . '/Protocol/SocketConnection.php';
include_once dirname(__DIR__) . '/Protocol/DefaultTransport.php';
use Evenement\EventEmitter;
use GuzzleHttp\Psr7\Uri;
use Protocol\SocketConnection;
use React\EventLoop\LoopInterface;
use SplObjectStorage;
use Zend\Log\LoggerInterface;

class TcpServer extends EventEmitter
{

    protected $_url;

    /**
     *
     * The raw streams connected to the Socket server (whether a handshake has taken place or not)
     *
     * @var SocketConnection[]|SplObjectStorage
     */
    protected $_streams;

    /**
     * The connected clients to the WebSocket server, a valid handshake has been performed.
     *
     * @var \SplObjectStorage|SocketTransportInterface[]
     */
    public $connections = [];

    protected $_context = null;

    public function __construct($url, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->loop = $loop;
        $this->_streams = new SplObjectStorage();

        $uri = new Uri($url);

        if ($uri->getScheme() != 'tcp' && $uri->getScheme() != 'ssl')
            throw new \InvalidArgumentException("Uri scheme must be tcp");

        $this->uri = $uri;

        $this->_context = stream_context_create();
        $this->_logger = $logger;
    }

    /**
     * Start the server
     */
    public function start()
    {
        $err = $errno = 0;

        $serverSocket = stream_socket_server($this->uri->getScheme() . '://' . $this->uri->getHost() . ':' . $this->uri->getPort(), $errno, $err, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $this->_context);


        $this->_logger->notice(sprintf("server listening on %s", $this->uri->getScheme() . '://' . $this->uri->getHost() . ':' . $this->uri->getPort()));

        if ($serverSocket == false) {
            $this->_logger->err("Error: $err");

            return;
        }

        $sockets = $this->_streams;
        $that = $this;
        $logger = $this->_logger;

        $this->loop->addReadStream($serverSocket, function ($serverSocket) use ($that, $logger, $sockets) {
            $newSocket = stream_socket_accept($serverSocket);

            if (false === $newSocket) {
                return;
            }

            stream_set_blocking($newSocket, 0);
            $client = new SocketConnection($newSocket, $that->loop, $logger);
            $sockets->attach($client);

            $client->on("message", function ($message) use ($that, $client, $logger) {
                $connection = $client->getTransport();
                $that->emit("message", array("client" => $connection, "message" => $message));
            });

            $client->on("close", function () use ($that, $client, &$sockets, $client) {
                $sockets->detach($client);
                $connection = $client->getTransport();
                if ($connection) {
                    $that->emit("disconnect", array("client" => $connection));
                }
            });

        });
    }

    public function getConnections()
    {
        return $this->connections;
    }

    public function getStreamContext()
    {
        return $this->_context;
    }

    public function setStreamContext($context)
    {
        $this->_context = $context;
    }

}