<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $config = require_once dirname(__DIR__) . '/config/main.php';

    if (!empty($_POST['command']) && !empty($_POST['id'])) {
        $loop = React\EventLoop\Factory::create();
        $dnsResolverFactory = new React\Dns\Resolver\Factory();
        $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        $connector = new React\SocketClient\Connector($loop, $dns);

        $connector->create('127.0.0.1', $config['servicePort'])->then(function (React\Stream\Stream $stream) {
            echo 'Команда успешно отправлена' . PHP_EOL;
            $stream->write($_POST['id'] . '|' . $_POST['command'] . PHP_EOL);
            $stream->end();
        });

        $loop->run();
    }
?>
<?php
    if (!isset($_POST['data'])) :
        ?>
        <form method="post">
            <input type="text" name="id" placeholder="ID"> <input type="text" name="command" placeholder="команда">
            <input type="submit" value="Отправить команду">
        </form>
    <?php endif; ?>