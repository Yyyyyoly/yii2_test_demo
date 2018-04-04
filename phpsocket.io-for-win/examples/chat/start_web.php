<?php
use WorkermanForWindows\Worker;
use WorkermanForWindows\WebServer;
use WorkermanForWindows\Autoloader;
use PHPSocketIOForWindows\SocketIO;

// composer autoload
require_once  __DIR__ . '/../../../../../vendor/autoload.php';

$web = new WebServer('http://0.0.0.0:2022');
$web->addRoot('localhost', __DIR__ . '/public');

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
