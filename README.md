# 7d2d-server-time

Displays the server time of a public 7 days 2 die server.

![MIT licence](https://img.shields.io/badge/license-MIT-green)
![PHP version](https://img.shields.io/badge/PHP-8.1-blue)

Output looks like

    Tag: 62 Zeit: 14:25
    Spieler online: 4

## Usage

```PHP
$server = new Server('yourserver.com');
echo $server->fetch()
    ->parse()
    ->toHtml();
```
