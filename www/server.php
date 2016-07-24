<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $loop = React\EventLoop\Factory::create();

    $socket = new React\Socket\Server($loop);

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);
//    $dnsResolver = $dnsResolverFactory->createCached('localhost', $loop);

    $factory = new React\HttpClient\Factory();
    $httpClient = $factory->create($loop, $dnsResolver);


    $socket->on('connection', function ($conn) use ($httpClient) {
        /* @var $conn \React\Socket\Connection */
        $conn->write("Hello client!\n");
        $conn->write("Welcome to this TCP server!\n");

        $conn->on('data', function ($data) use ($conn, $httpClient) {
            echo $data;

            //TODO: Create background request with RabbitMQ
            //TODO: течет память!
            /* @var $httpClient \React\HttpClient\Client */
            $request = $httpClient->request('POST', 'http://apiserver.host', [
                'Content-Length' => strlen($data),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]);
            $request->on('response', function ($response) {
                $buffer = '';
                echo "received" . PHP_EOL;
                $response->on(
                    'data',
                    function ($data, $response) use (&$buffer) {
                        $buffer .= $data;
                    }
                );
                $response->on('end', function () use (&$buffer, $response) {
                    echo 'response data received' . PHP_EOL;
                    unset($buffer);
                });
            });
            $request->end($data);
            unset($data);
            echo memory_get_usage() . PHP_EOL;
//            $conn->close();
        });

        $conn->on('close', function ($conn) {
            /* @var $conn \React\Socket\Connection */
            echo 'client connection closed' . PHP_EOL;
        });
    });

    $socket->listen(8001);

    $loop->run();