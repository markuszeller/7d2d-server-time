<?php
declare(strict_types=1);

namespace markuszeller\server;

class Server
{
    private const KEY_AND_VALUE_LENGTH = 2;
    private const SECONDS_PER_DAY      = 24000;
    private const REQUIRED_KEYS        = ['CurrentServerTime', 'CurrentPlayers'];

    private array $server = [];
    private float $day    = 0;
    private float $hour   = 0;
    private float $minute = 0;

    private bool $isFetched = false;
    private bool $isParsed  = false;

    public function __construct(
        private string $hostname,
        private int    $port = 26900,
        private int    $timeout = 5
    )
    {
    }

    public function fetch(): self
    {
        $handle = fsockopen(
            $this->hostname,
            $this->port,
            $errorCode,
            $errorMessage,
            $this->timeout
        );

        if (!$handle) {
            throw new \Exception("Can not connect to server. $errorMessage");
        }

        while ($data = fgets($handle)) {
            $map = array_map('trim', explode(':', $data));
            if (count($map) !== self::KEY_AND_VALUE_LENGTH) continue;
            [$key, $value] = $map;
            if (!$key) continue;
            if (!$value) $value = '';
            $this->server[$key] = substr($value, 0, -1);

        }
        fclose($handle);

        $this->isFetched = true;
        $this->isParsed  = false;

        return $this;
    }

    public function parse(): self
    {
        if (!$this->isFetched) {
            throw new \LogicException('Call fetch() before.');
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (!isset($this->server[$key])) {
                throw new \Exception('Missing Server key $key');
            }
        }

        $this->day    = $this->server['CurrentServerTime'] / self::SECONDS_PER_DAY + 1;
        $secondsOver  = $this->server['CurrentServerTime'] % self::SECONDS_PER_DAY;
        $this->hour   = $secondsOver / (self::SECONDS_PER_DAY / 24);
        $this->minute = fmod($this->hour, 1) * 60;

        $this->isParsed = true;

        return $this;
    }

    public function toHtml(): string
    {
        ob_start();
        if (!$this->isParsed) {
            throw new \LogicException('Call parse() before.');
        }
        echo "<pre>\n";
        printf("<b>Tag:</b> %d <b>Zeit:</b> %02d:%02d\n", $this->day, $this->hour, $this->minute);
        printf("<b>Spieler online:</b> %d\n", $this->server['CurrentPlayers']);
        echo "</pre>\n";

        return (string) ob_get_clean();
    }
}
