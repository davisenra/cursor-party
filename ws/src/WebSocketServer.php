<?php

declare(strict_types=1);

namespace CursorParty;

use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Psr\Log\LoggerInterface;
use Swoole\WebSocket\Server;

final class WebSocketServer
{
    private Server $server;
    /** @var array<int, Cursor> $clients */
    private array $clients = [];

    public function __construct(
        private string $host,
        private int $port,
        private readonly LoggerInterface $logger
    ) {
        $this->server = new Server($host, $port);
        $this->server->on('start', fn () => $this->logger->info('Server started'));
        $this->server->on('open', fn ($_, Request $request) => $this->onOpen($request));
        $this->server->on('close', fn ($_, int $clientId) => $this->onClose($clientId));
        $this->server->on('message', fn ($_, Frame $frame) => $this->onMessage($frame));
    }

    public function start(): void
    {
        $this->server->start();
    }

    private function onOpen(Request $request): void
    {
        $hasUsername = $request->get !== null && !empty($request->get['username']);
        if (!$hasUsername) {
            $this->server->disconnect($request->fd);
            return;
        }
        $username = (string) $request->get['username'];
        $this->logger->info("$username has joined the server");
        $this->clients[$request->fd] = new Cursor($username, 0, 0);
    }

    private function onClose(int $clientId): void
    {
        $username = $this->clients[$clientId]->username;
        $this->logger->info("$username has left the server");
        unset($this->clients[$clientId]);
    }

    private function onMessage(Frame $frame): void
    {
        /** @var object{x: int, y: int} $payload */
        $payload = json_decode($frame->data);
        $this->clients[$frame->fd]->x = $payload->x;
        $this->clients[$frame->fd]->y = $payload->y;

        foreach ($this->clients as $clientId => $_) {
            $cursors = array_filter($this->clients, fn ($id) => $id !== $clientId, ARRAY_FILTER_USE_KEY);
            $this->server->push($clientId, json_encode(['cursors' => $cursors]));
        }
    }
}
