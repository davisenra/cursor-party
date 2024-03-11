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

$server->on('start', function (Server $server) use ($logger) {
    $logger->info('Server running at: ws://localhost:9502');
});

$server->on('open', function (Server $server, Request $request) use ($logger, &$clients) {
    $hasUsername = $request->get !== null && !empty($request->get['username']);
    if (!$hasUsername) {
        $server->disconnect($request->fd);
        return;
    }
    $username = (string) $request->get['username'];
    $logger->info("$username has joined the server");
    $clients[$request->fd] = new Cursor($username, 0, 0);
});

$server->on('close', function (Server $server, int $fd) use ($logger, &$clients) {
    $logger->info("[$fd] has left the server");
    unset($clients[$fd]);
});

$server->on('message', function (Server $server, Frame $frame) use (&$clients) {
    /** @var object{x: int, y: int} $payload */
    $payload = json_decode($frame->data);
    $clients[$frame->fd]->x = $payload->x;
    $clients[$frame->fd]->y = $payload->y;

    foreach ($clients as $clientId => $_) {
        $cursors = array_filter(
            $clients,
            fn ($id) => $id !== $clientId,
            ARRAY_FILTER_USE_KEY
        );
        $server->push($clientId, json_encode(['cursors' => $cursors]));
    }
});

$server->start();
