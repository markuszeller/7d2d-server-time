<?php
declare(strict_types=1);

namespace markuszeller\server;

class Server implements \Stringable
{
    private const KEY_AND_VALUE_LENGTH = 2;
    private const SECONDS_PER_DAY      = 24000;
    private const REQUIRED_KEYS        = ['CurrentServerTime', 'CurrentPlayers'];
    private const HOURS_PER_DAY        = 24;
    private const SECONDS_PER_MINUTE   = 60;
    private const STARTING_DAY         = 1;
    private const FMOD_DIVISOR         = 1;

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

        $this->day    = $this->server['CurrentServerTime'] / self::SECONDS_PER_DAY + self::STARTING_DAY;
        $secondsOver  = $this->server['CurrentServerTime'] % self::SECONDS_PER_DAY;
        $this->hour   = $secondsOver / (self::SECONDS_PER_DAY / self::HOURS_PER_DAY);
        $this->minute = fmod($this->hour, self::FMOD_DIVISOR) * self::SECONDS_PER_MINUTE;

        $this->isParsed = true;

        return $this;
    }

    public function toHtml(): string
    {
        if (!$this->isParsed) {
            throw new \LogicException('Call parse() before.');
        }

        ob_start();

        echo "<pre>\n";
        printf("<b>Tag:</b> %d <b>Zeit:</b> %02d:%02d\n", $this->day, $this->hour, $this->minute);
        printf("<b>Spieler online:</b> %d\n", $this->server['CurrentPlayers']);
        echo "</pre>\n";

        return (string) ob_get_clean();
    }

    public function __toString(): string
    {
        if (!$this->isParsed) {
            throw new \LogicException('Call parse() before.');
        }

        $playerText = match ($this->server['CurrentPlayers']) {
            "0" => 'ist kein',
            "1" => 'ist ein',
            default => "sind {$this->server['CurrentPlayers']}",
        };
        return
            sprintf("Tag %d, Zeit %d Uhr %d. ", $this->day, $this->hour, $this->minute) .
            sprintf("Es %s Spieler online.", $playerText);
    }
}
