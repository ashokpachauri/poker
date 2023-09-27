<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/boot.php';
require __DIR__ . '/wss.php';

if ( !boolval(USEWEBSOCKETS) )
    die("enable websockets in OPS settings\n");

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$game = new OPSGame();
$wss  = new WsServer($game);
$http = new HttpServer($wss);

$server = IoServer::factory(
    $http,
    3000
   // empty(WEBSOCKET_PORT) ? ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443) ? 443 : 80) : WEBSOCKET_PORT
);

$wss->enableKeepAlive($server->loop, 5);
$server->run();
