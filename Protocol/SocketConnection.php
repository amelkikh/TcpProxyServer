<?php

namespace Protocol;

use Exception;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use Zend\Log\LoggerInterface;

/**
 * Class SocketConnection
 *
 * @package Protocol
 */
class SocketConnection extends Connection
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var SocketTransportInterface
     */
    private $_transport = null;
    private $_lastChanged = null;

    public function __construct($socket, LoopInterface $loop, $logger)
    {
        parent::__construct($socket, $loop);

        $this->_lastChanged = time();
        $this->logger = $logger;
    }

    public function handleData($stream)
    {
        if (!is_resource($stream)) {
            $this->close();
            return;
        }

        $data = fread($stream, $this->bufferSize);

        if ('' === $data || false === $data) {
            $this->close();
        } else {
            $this->onData($data);
        }
    }

    private function onData($data)
    {
        try {
            $this->_lastChanged = time();

            if ($this->_transport)
                $this->emit('data', [$data, $this]);
            else {
                $this->establishConnection($data);
                $this->emit('data', [$data, $this]);
            }
        } catch (Exception $e) {
            $this->logger->err("Error while handling incoming data. Exception message is: " . $e->getMessage());
            $this->close();
        }
    }

    public function setTransport(SocketTransportInterface $con)
    {
        $this->_transport = $con;
    }

    public function establishConnection($data)
    {
        $this->_transport = new DefaultTransport($this, $data, $this->logger);
        $myself = $this;

        $this->_transport->on("connect", function () use ($myself) {
            $myself->emit("connect", [$myself]);
        });

        $this->_transport->on("message", function ($message) use ($myself) {
            $myself->emit("message", ["message" => $message]);
        });

    }

    public function getLastChanged()
    {
        return $this->_lastChanged;
    }

    /**
     *
     * @return SocketTransportInterface
     */
    public function getTransport()
    {
        return $this->_transport;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}