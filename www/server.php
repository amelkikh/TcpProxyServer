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
    $server = new TcpServer('tcp://0.0.0.0:'.$config['tcpServerSettings']['clientPort'], $loop, $logger);

    $server->on('message', function ($client, $message) use ($httpClient, $config, $logger, $server) {
        list($id) = explode(',', $message);
        $logger->notice('Message: '.$message);
        if (!isset($server->connections[$id]) && !$client->getId()) {
            $client->setId($id);
            $server->connections[$id]  = $client;
        }
        /**
         * Асинхронная отправка данных на сервер приложения
         */
        $request = new Request('POST', $config['appServerEndpoint']['httpHost'], [
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
            },
            function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );
        $promise->wait();
    });

    $server->on('close', function($client) use ($server, $logger) {
        $logger->notice('Client disconnected');
        $id = $client->getId();
        if (isset($server->connections[$id])) {
            unset($server->connections[$id]);
        }
        $client->close();
    });



    $server->start();

    $commandServer = new TcpServer('tcp://0.0.0.0:'.$config['tcpServerSettings']['servicePort'], $loop, $logger);

    $commandServer->on('message', function ($client, $message) use ($logger, $server) {
        $logger->notice('Command from server: ' . $message);
        list($id, $command) = explode('|', $message);
        if (isset($server->connections[$id])) {
            /* @var $remoteClient \Protocol\DefaultTransport */
            $remoteClient = $server->connections[$id];
            $remoteClient->sendString($command);
        }
    });

    $commandServer->on('close', function ($client) use ($server, $httpClient, $logger) {
        $logger->notice('Server connect close');
    });

    $commandServer->start();


    /************* Тестовый TCP клиент часов ****************/


//    $client = new \Client\Socket($config['tcpServerSettings']['tcpHost'].':'.$config['tcpServerSettings']['clientPort'], $loop, $logger);
//    $id = 1;
//    $client->on("connect", function () use ($logger, $client, $loop, $id) {
//        $logger->notice("Connected!");
//        $loop->addPeriodicTimer(1, function ($timer) use ($logger, $client, $id) {
//            /* @var $timer \React\EventLoop\Timer\Timer */
//            $logger->notice('Send message to server');
//            $client->send($id . ',' . rand(0, 1000000) . '.' . rand(0, 1000000) . ',' . time());
////            $client->close();
////            $timer->cancel();
//        });
//    });

//    $client->on("message", function ($message) use ($client, $logger) {
//        $logger->notice("Got message from server: " . $message);
//    });


//    $client->open();

    $loop->run();