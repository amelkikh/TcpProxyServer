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

    $server = new TcpServer('tcp://127.0.0.1:8001', $loop, $logger);
    $server->start();

//    $commandServer = new TcpServer('tcp://127.0.0.1:8002', $loop, $logger);
//    $commandServer->start();

    /**************************************/


    $client = new \Client\Socket('tcp://127.0.0.1:8001', $loop, $logger);
    $client->on("connect", function () use ($logger, $client) {
        $logger->notice("Connected!");
        $client->send("Hello world!");
    });

    $client->on("message", function ($message) use ($client, $logger) {
        $logger->notice("Got message: " . $message->getData());
        $client->close();
    });
    $client->open();

    $loop->addPeriodicTimer(1, function() use($server, $logger){
        $time = new DateTime();
        $string = $time->format("Y-m-d H:i:s");
        $logger->notice("Broadcasting time to all clients: $string");
        var_dump(count($server->getConnections()));
        foreach($server->getConnections() as $client) {
            $logger->notice('Send to Client ID: '.$client->getId());
            $client->sendString($string);
        }
    });

//    $dnsResolverFactory = new React\Dns\Resolver\Factory();
//    $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
//    $connector = new React\SocketClient\Connector($loop, $dns);
//
//    $id = uniqid();
//    $connector->create('127.0.0.1', 8001)->then(function (React\Stream\Stream $stream) use ($id, $loop) {
//
//        $loop->addPeriodicTimer(1, function (React\EventLoop\Timer\Timer $timer) use ($id, $loop, $stream) {
//            $stream->write($id.'|'.time().'|'.mktime() . PHP_EOL);
////            if ($i >= 15) {
////                $loop->cancelTimer($timer);
////                $stream->close();
////            }
//        });
//        $stream->on('data', function ($data) {
//            echo $data . PHP_EOL;
//        });
//
//        $stream->on('close', function ($data) {
//            echo 'stream closed' . PHP_EOL;
//            exit;
//        });
//
//    });
    /**************************************/
    $loop->run();

    //    /* @var $httpClient Client */
    //
    //    $httpClient = new Client();
    //    /**
    //     * Асинхронная отправка данных на сервер приложения
    //     */
    //    $request = new Request('POST', $config['appServerEndpoint']['host'], [
    //        'secure' => false
    //    ]);
    //
    //    $promise = $httpClient->sendAsync($request,[
    //        'form_params' => [
    //            'data' => $data
    //        ]
    //    ]);
    //    $promise->then(
    //        function (ResponseInterface $res) {
    //            echo $res->getBody().PHP_EOL;
    //        },
    //        function (RequestException $e) {
    //            echo $e->getMessage() . "\n";
    //            echo $e->getRequest()->getMethod();
    //        }
    //    );
    //    $promise->wait();