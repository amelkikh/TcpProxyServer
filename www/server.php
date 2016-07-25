<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    use GuzzleHttp\Client;
    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Exception\RequestException;
    use GuzzleHttp\Psr7\Request;

    $loop = React\EventLoop\Factory::create();

    $socket = new React\Socket\Server($loop);


    $httpClient = new Client();

    /**
     * Событие подключения сокет-клиента к серверу
     * TODO: Добавить класс для сокет клиента, логгер
     */
    $socket->on('connection', function ($conn) use ($httpClient) {
        /* @var $conn \React\Socket\Connection */
        $conn->write("Hello client!\n");
        $conn->write("Welcome to this TCP server!\n");

        // Событие получения данных
        $conn->on('data', function ($data) use ($conn, $httpClient) {

            echo $data;

            /* @var $httpClient Client */
            /**
             * Асинхронная отправка данных на сервер приложения
             * http://tcpproxyserver.loc - локальный сервер для этого проекта
             * TODO: Указать реальный хост приложения для проксирования данных
             */
            $request = new Request('POST', 'http://tcpproxyserver.loc', []);

            $promise = $httpClient->sendAsync($request,[
                'form_params' => [
                    'data' => $data
                ]
            ]);
            $promise->then(
                function (ResponseInterface $res) {
                    echo $res->getBody().PHP_EOL;
                },
                function (RequestException $e) {
                    echo $e->getMessage() . "\n";
                    echo $e->getRequest()->getMethod();
                }
            );
            $promise->wait();

            // смотрим потребляемую память
            echo memory_get_usage() . PHP_EOL;
//            $conn->close();
        });

        // Событие закрытия сокет соединения клиента.
        $conn->on('close', function ($conn) {
            /* @var $conn \React\Socket\Connection */
            echo 'client connection closed' . PHP_EOL;
        });
    });

    // слушаем коннекты на порту
    $socket->listen(8001);

    // работаем с событиями
    $loop->run();