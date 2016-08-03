<?php
    $config = [
        'tcpServerSettings' => [
            'tcpHost' => '',
            'clientPort' => 8001,
            'servicePort' => 8002,
        ],
        'appServerEndpoint' => [
            'httpHost' => 'http://httpbin.org/post',
        ],
    ];

    if (empty($config['tcpServerSettings']['tcpHost'])) {
        throw new Exception('Please setup config tcpServerSettings->tcpHost, e.g tcp://my-tcp-server.com');
    }

    if (empty($config['appServerEndpoint']['httpHost'])) {
        throw new Exception('Please setup config appServerEndpoint->httpHost, e.g http://my-app-backend.com');
    }

    return $config;