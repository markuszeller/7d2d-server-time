<?php

use markuszeller\server\Server;

require_once './src/Server.class.php';

$server = new Server('yourserver.com');
echo $server->fetch()
    ->parse()
    ->toHtml();
