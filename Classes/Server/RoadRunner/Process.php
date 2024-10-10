<?php

namespace Crazy252\Typo3Kate\Server\RoadRunner;

use Crazy252\Typo3Kate\Exception\RoadRunner\RoloadException;
use Crazy252\Typo3Kate\Exception\RoadRunner\RpcNotFoundException;
use Crazy252\Typo3Kate\Traits\Find;
use Crazy252\Typo3Kate\Utility\ArrayUtility;
use Symfony\Component\Process\Process as SymfonyProcess;
use TYPO3\CMS\Core\Core\Environment;

class Process
{
    use Find;

    protected Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function isRunning(): bool
    {
        $context = $this->context->read();

        return $context['processId'] and posix_kill($context['processId'], 0);
    }

    public function reload(): bool
    {
        $context = $this->context->read();

        $rpc = ArrayUtility::get($context['state'], 'rpc');
        if (!is_string($rpc)) {
            throw new RpcNotFoundException('RPC address cannot be read');
        }

        $process = new SymfonyProcess(
            [$this->findRoadRunnerBinary(), 'reset', '-o', $rpc, '-s'],
            Environment::getProjectPath()
        );

        $process->run(function ($type, $buffer) {
            if ($type === SymfonyProcess::ERR) {
                throw new RoloadException('Cannot reload RoadRunner: ' . $buffer);
            }
            return true;
        });

        return true;
    }

    public function stop(): bool
    {
        $context = $this->context->read();

        return posix_kill($context['processId'], SIGTERM);
    }
}
