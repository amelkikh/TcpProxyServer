<?php

    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $config = require_once dirname(__DIR__) . '/config.php';
    $loop = React\EventLoop\Factory::create();

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
    $connector = new React\SocketClient\Connector($loop, $dns);

    $id = 1;

    //TODO: Указать адрес TCP сервера
    $connector->create($config['tcpServerSettings']['tcpHost'], $config['tcpServerSettings']['servicePort'])->then(function (React\Stream\Stream $stream) use ($id, $loop) {

        $loop->addPeriodicTimer(1, function (React\EventLoop\Timer\Timer $timer) use ($id, $loop, $stream) {
            $stream->write($id . '|' . 'remote-reset' . PHP_EOL);
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