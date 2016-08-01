<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    if (!empty($_POST['command']) && !empty($_POST['id'])) {

        $loop = React\EventLoop\Factory::create();

        $dnsResolverFactory = new React\Dns\Resolver\Factory();
        $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        $connector = new React\SocketClient\Connector($loop, $dns);

        $connector->create('127.0.0.1', 8002)->then(function (React\Stream\Stream $stream) use ($loop) {
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
    <?php else : ?>
<?php
$path = __DIR__ . '/../logs/';
if (!is_dir($path)) {
    mkdir($path, 0755);
}
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream($path . date('Y-m-d') . '.txt');
$logger->addWriter($writer);
$logger->info($_POST['data']);
echo 'OK';
?>
    <?php endif; ?>