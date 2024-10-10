<?php

namespace Crazy252\Typo3Kate\Traits;

trait Output
{
    /**
     * A list of error messages that should be ignored.
     *
     * @var array
     */
    protected $ignoreMessages = [
        'destroy signal received',
        'req-resp mode',
        'scan command',
        'sending stop request to the worker',
        'stop signal received, grace timeout is: ',
        'exit forced',
        'worker allocated',
        'worker is allocated',
        'worker constructed',
        'worker destructed',
        'worker destroyed',
        '[INFO] RoadRunner server started; version:',
        '[INFO] sdnotify: not notified',
        'exiting; byeee!!',
        'storage cleaning happened too recently',
        'write error',
        'unable to determine directory for user configuration; falling back to current directory',
        '$HOME environment variable is empty',
        'unable to get instance ID',
    ];

    public function raw(string $string): void
    {
        if (!$this->messageIsIgnored($string)) {
            $this->output->writeln($string);
        }
    }

    /**
     * @param int|string|null $verbosity
     */
    public function info(string $string, $verbosity = null): void
    {
        $this->label($string, $verbosity, 'INFO', 'blue', 'white');
    }

    /**
     * @param int|string|null $verbosity
     */
    public function error(string $string, $verbosity = null): void
    {
        $this->label($string, $verbosity, 'ERROR', 'red', 'white');
    }

    /**
     * @param int|string|null $verbosity
     */
    public function warn(string $string, $verbosity = null): void
    {
        $this->label($string, $verbosity, 'WARN', 'yellow', 'black');
    }

    /**
     * @param int|string|null $verbosity
     */
    public function label(string $string, $verbosity, string $level, string $background, string $foreground): void
    {
        if (!empty($string) and !$this->messageIsIgnored($string)) {
            $this->output->writeln("  <bg=$background;fg=$foreground;options=bold> $level </> $string");
            /*$this->output->writeln([
                '',
                "  <bg=$background;fg=$foreground;options=bold> $level </> $string",
            ], $this->parseVerbosity($verbosity));*/
        }
    }

    public function messageIsIgnored(string $message): bool
    {
        $result = array_filter($this->ignoreMessages, function ($line) use ($message) {
            return str_starts_with($message, $line);
        });

        return count($result) > 0;
    }

    /**
     * @param int|string $duration
     */
    public function requestInfoMessage(int $status, string $method, string $uri, int $bytes, $duration): string
    {
        $method = strtoupper($method);

        $color = 'white';
        if ($status >= 100) {$color = 'green';}
        if ($status >= 300) {$color = 'cyan';}
        if ($status >= 400) {$color = 'yellow';}
        if ($status >= 500) {$color = 'error';}

        return sprintf('  <fg=%s>%s %s</>  <fg=white>%s  %s  %s</>',
            $color, $status, $method, $uri,
            $this->formatBytes($bytes), $this->formatDuration($duration));
    }

    public function formatBytes(int $bytes): string
    {
        $index = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        return sprintf('%.02F', $bytes / pow(1024, $index)) * 1 . ' ' . $sizes[$index];
    }

    /**
     * @param int|string|float $time
     * @return float|string
     */
    public function formatDuration($time)
    {
        if (str_ends_with($time, 'ms')) {
            return $time;
        }
        if (str_ends_with($time, 'µs')) {
            return (mb_substr($time, 0, -2) * 0.001) . 'ms';
        }
        return $time . 'ms';
    }
}
