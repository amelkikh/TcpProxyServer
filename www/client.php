<?php

    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $loop = React\EventLoop\Factory::create();

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
    $connector = new React\SocketClient\Connector($loop, $dns);

    $id = rand(0,100);
    $connector->create('127.0.0.1', 8001)->then(function (React\Stream\Stream $stream) use ($id, $loop) {

        $loop->addPeriodicTimer(0.5, function (React\EventLoop\Timer\Timer $timer) use ($id, $loop, $stream) {
            $stream->write($id.'|'.time() . PHP_EOL);
//            if ($i >= 15) {
//                $loop->cancelTimer($timer);
//                $stream->close();
//            }
        });
        $stream->on('data', function ($data) {
            echo $data . PHP_EOL;
        });

        $stream->on('close', function ($data) {
            echo 'stream closed' . PHP_EOL;
            exit;
        });

    });

    $loop->run();