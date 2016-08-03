<?php
namespace Protocol;

include_once dirname(__DIR__) . '/Protocol/TransportInterface.php';

interface SocketTransportInterface extends TransportInterface
{

    public function getId();

    public function setId($id);

    public function handleData(&$data);

    public function getIp();

    public function close();

    public function setData($key, $value);

    public function getData($key);
}
