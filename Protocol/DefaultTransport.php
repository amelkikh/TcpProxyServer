<?php
    namespace Protocol;

    include_once dirname(__DIR__) . '/Protocol/SocketTransport.php';
    use Evenement\EventEmitter;
    use GuzzleHttp\Psr7\Request;
    use React\Stream\WritableStreamInterface;
    use Zend\Log\LoggerAwareInterface;
    use Zend\Log\LoggerInterface;

    class DefaultTransport extends SocketTransport
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
        protected $_socket = NULL;
        protected $_cookies = [];
        public $parameters = NULL;

        protected $_eventManger;

        protected $data = [];

        public function __construct(WritableStreamInterface $socket)
        {
            $this->_socket = $socket;
            $this->_id = uniqid("connection-");

            $that = $this;

            $buffer = '';
            $socket->on("data", function ($data) use ($that, &$buffer) {
                $buffer .= $data;
                $that->handleData($buffer);
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

        public function getData($key)
        {
            return $this->data[$key];
        }

        public function respondTo(Request $request)
        {
            // TODO: Implement respondTo() method.
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
    }