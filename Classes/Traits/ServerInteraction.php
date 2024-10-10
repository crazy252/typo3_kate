<?php

 namespace Crazy252\Typo3Kate\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

trait ServerInteraction
{
    public function run(Process $server)
    {
        while (!$server->isStarted()) {
            sleep(1);
        }

        try {
            $usleepBetweenIterations = 10 * 1000;

            while ($server->isRunning()) {
                $this->output->write($server->getIncrementalOutput());

                usleep($usleepBetweenIterations);
            }

            $this->output->write($server->getIncrementalOutput());
        } catch (\Exception $exception) {
            return Command::SUCCESS;
        } finally {
            return $this->stop();
        }
    }

    public function getServerOutput(Process $server): array
    {
        $output = [
            $server->getIncrementalOutput(),
            $server->getIncrementalErrorOutput(),
        ];

        $server->clearOutput()->clearErrorOutput();

        return $output;
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM, SIGHUP];
    }

    public function handleSignal(): void
    {
        $this->stop();

        exit(0);
    }
}
