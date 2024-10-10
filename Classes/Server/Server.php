<?php

namespace Crazy252\Typo3Kate\Server;

use Crazy252\Typo3Kate\Traits\Find;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class Server
{
    use Find;

    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function copyFile(string $source, string $target): void
    {
        $pieces = explode('/', $target);
        $fileName = end($pieces);

        if (file_exists($source)) {
            $this->output->writeln(
                sprintf('<comment>The existing %s was renamed to %s.backup</comment>', $fileName, $fileName)
            );

            rename($target, $target . '.backup');
        }

        copy($source, $target);
    }

    protected function run(Process $server)
    {
        while (!$server->isStarted()) {
            sleep(1);
        }

        try {
            $usleepBetweenIterations = 10 * 1000;

            while ($server->isRunning()) {
                $this->writeServerOutput($server);

                usleep($usleepBetweenIterations);
            }

            $this->writeServerOutput($server);
        } catch (\Exception $exception) {
            return Command::SUCCESS;
        } finally {
            return $this->stop();
        }
    }

    protected function getServerOutput(Process $server): array
    {
        $output = [
            $server->getIncrementalOutput(),
            $server->getIncrementalErrorOutput(),
        ];

        $server->clearOutput()->clearErrorOutput();

        return $output;
    }

    protected function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM, SIGHUP];
    }

    protected function handleSignal(): void
    {
        $this->stop();

        exit(0);
    }
}
