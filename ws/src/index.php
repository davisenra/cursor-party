<?php

declare(strict_types=1);

use CursorParty\Cursor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swoole\Websocket\Server;
use Swoole\Websocket\Frame;
use Swoole\Http\Request;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var array<int, Cursor> $clients */
$clients = [];

$logger = new Logger('mouse-party', [new StreamHandler('php://stdout')]);
$server = new Server('localhost', 9502);

$server->on('start', fn (Server $server) => $logger->info('Server running at: ws://localhost:9502'));

$server->on('open', function(Server $server, Request $req) use ($logger, &$clients) {
    $logger->info("New connection: $req->fd");
    $clients[$req->fd] = new Cursor(0, 0);
});

$server->on('close', function(Server $server, int $fd) use ($logger, &$clients) {
    $logger->info("Connection closed: $fd");
    unset($clients[$fd]);
});

$server->on('message', function(Server $server, Frame $frame) use ($logger, &$clients) {
    /** @var object{x: int, y: int} $payload */
    $payload = json_decode($frame->data);
    $clients[$frame->fd]->x = $payload->x;
    $clients[$frame->fd]->y = $payload->y;

    foreach ($clients as $clientId => $mousePosition) {
        $mousePositions = array_filter(
            $clients,
            fn ($client, $id) => $id !== $clientId,
            ARRAY_FILTER_USE_BOTH
        );
        $server->push($clientId, json_encode(['cursors' => $mousePositions]));
    }
});

$server->start();