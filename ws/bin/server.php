<?php

declare(strict_types=1);

use CursorParty\WebSocketServer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new Logger('mouse-party', [new StreamHandler('php://stdout')]);
$server = new WebSocketServer('localhost', 9502, $logger);
$server->start();
