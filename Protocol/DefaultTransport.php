<?php
namespace Protocol;

include_once dirname(__DIR__) . '/Protocol/SocketTransport.php';
use React\Stream\WritableStreamInterface;
use Zend\Log\LoggerInterface;

class DefaultTransport extends SocketTransport
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var SocketConnection
     */
    protected $_socket = NULL;
    protected $_cookies = [];
    public $parameters = NULL;

    protected $_eventManger;

    protected $data = [];

    public function __construct(WritableStreamInterface $socket)
    {
        $this->_socket = $socket;
        $this->_id = false;

        $that = $this;

        $socket->on("data", function ($data) use ($that) {
            $that->handleData($data);
        });

        $socket->on("close", function ($data) use ($that) {
            $that->emit("close", func_get_args());
        });

        $socket->on('connect', function () use ($that) {
            $that->emit("connect");
        });
    }

    public function getIp()
    {
        return $this->_socket->getRemoteAddress();
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getSocket()
    {
        return $this->_socket;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getData($key = null)
    {
        if ($key && isset($this->data[$key])) {
            return $this->data[$key];
        }
        return $this->data;

    }

    public function handleData(&$data)
    {
        $this->emit("message", ['message' => $data]);
    }

    public function close()
    {
        $this->_socket->close();
    }

    public function sendString($string)
    {
        return $this->sendMessage($string);
    }

    public function sendMessage($msg)
    {
        $this->_socket->write($msg);
    }
}