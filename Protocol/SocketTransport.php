<?php
namespace Protocol;
include_once dirname(__DIR__).'/Protocol/SocketTransportInterface.php';
use Evenement\EventEmitter;
use React\Stream\WritableStreamInterface;
use Zend\Log\LoggerAwareInterface;
use Zend\Log\LoggerInterface;

abstract class SocketTransport extends EventEmitter implements SocketTransportInterface, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     *
     * @var SocketConnection
     */
    protected $_socket = null;
    protected $_cookies = array();
    public $parameters = null;

    protected $_eventManger;

    protected $data = array();

    public function __construct(WritableStreamInterface $socket)
    {
        $this->_socket = $socket;
        $this->_id = uniqid("connection-");

        $that = $this;

        $buffer = '';
        $socket->on("data", function($data) use ($that, &$buffer){
            $buffer .= $data;
            $that->handleData($buffer);
        });

        $socket->on("close", function($data) use ($that){
            $that->emit("close", func_get_args());
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

    public function setLogger(LoggerInterface $logger){
        $this->logger = $logger;
    }

    public function sendMessage($msg)
    {
        $this->_socket->write($msg);
    }

    public function setData($key, $value){
        $this->data[$key] = $value;
    }

    public function getData($key){
        return $this->data[$key];
    }
}