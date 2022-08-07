<?php

use markuszeller\server\Server;

require_once './src/Server.class.php';

$server = new Server('yourserver.com');

try {
    echo $server->fetch()
        ->parse()
        ->toHtml();
} catch (\Exception $exception) {
    echo "<h1>Error</h1><pre>{$exception->getMessage()}</pre>";
}
