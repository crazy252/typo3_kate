<?php

namespace Crazy252\Typo3Kate\Server;

interface ServerInterface
{
    public function install(): void;

    public function start(): int;

    public function reload(): int;

    public function stop(): int;
}
