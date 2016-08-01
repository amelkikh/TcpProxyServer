<?php
namespace Protocol;

include_once dirname(__DIR__).'/Protocol/TransportInterface.php';
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

interface SocketTransportInterface extends TransportInterface
{

    public function getId();

    public function respondTo(Request $request);

    public function handleData(&$data);

    public function getIp();

    public function close();

    public function setData($key, $value);

    public function getData($key);
}
