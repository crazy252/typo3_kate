<?php

namespace Crazy252\Typo3Kate\Server\RoadRunner;

use Crazy252\Typo3Kate\Exception\RoadRunner\ContextException;
use TYPO3\CMS\Core\Core\Environment;

class Context
{
    protected string $path;

    public function __construct()
    {
        $this->path = Environment::getProjectPath() . '/kate.process';
    }

    public function read(): array
    {
        $state = [];
        if (is_readable($this->path)) {
            $state = json_decode(file_get_contents($this->path), true);
        }

        return [
            'processId' => $state['processId'] ?? null,
            'state' => $state['state'] ?? [],
        ];
    }

    public function writeProcessId(int $processId): void
    {
        $this->writeable();

        $content = array_merge($this->read(), ['processId' => $processId]);
        file_put_contents($this->path, json_encode($content));
    }

    public function writeState(array $state): void
    {
        $this->writeable();

        $content = array_merge($this->read(), ['state' => $state]);
        file_put_contents($this->path, json_encode($content));
    }

    public function delete(): bool
    {
        return $this->writeable() and unlink($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }

    private function writeable(): bool
    {
        if (!is_writable($this->path) and !is_writable(dirname($this->path))) {
            throw new ContextException('Unable to write to process ID file.');
        }
        return true;
    }
}
