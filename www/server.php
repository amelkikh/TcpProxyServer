<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/Server/TcpServer.php';
require_once dirname(__DIR__) . '/Client/Socket.php';
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Server\TcpServer;

$config = require_once dirname(__DIR__) . '/config.php';

$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);
$loop = React\EventLoop\Factory::create();
/* @var $httpClient Client */

$httpClient = new Client();
$server = new TcpServer('tcp://127.0.0.1:8001', $loop, $logger);

$server->on('message', function ($transport, $message) use ($httpClient, $config, $logger) {
    /**
     * Асинхронная отправка данных на сервер приложения
     */
    $request = new Request('POST', $config['appServerEndpoint']['host'], [
        'secure' => false
    ]);

    $promise = $httpClient->sendAsync($request, [
        'form_params' => [
            'data' => $message
        ]
    ]);
    $promise->then(
        function (ResponseInterface $res) {
            echo $res->getBody() . PHP_EOL;
            echo 'OK' . PHP_EOL;
        },
        function (RequestException $e) {
            echo $e->getMessage() . "\n";
            echo $e->getRequest()->getMethod();
        }
    );
    $promise->wait();
});

$server->start();

//    $commandServer = new TcpServer('tcp://127.0.0.1:8002', $loop, $logger);
//    $commandServer->start();

/**************************************/


$client = new \Client\Socket('tcp://127.0.0.1:8001', $loop, $logger);
$client->on("connect", function () use ($logger, $client, $loop) {
    $logger->notice("Connected!");
    $loop->addPeriodicTimer(1, function () use ($logger, $client) {
        $logger->notice('Send message to server');
        $client->send("Hello world!");
    });
});

$client->on("message", function ($message) use ($client, $logger) {
    $logger->notice("Got message: " . $message->getData());
    $client->close();
});


$client->open();

$loop->run();