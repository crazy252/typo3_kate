<?php

namespace Crazy252\Typo3Kate\Trait;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use TYPO3\CMS\Core\Core\Environment;

trait Find
{
    /**
     * Get the composer command for the environment
     */
    protected function findComposer(): string
    {
        $composerPath = Environment::getProjectPath() . '/composer.phar';
        if (!file_exists($composerPath)) {
            $composerPath = (new ExecutableFinder())->find('composer');
        }

        return sprintf('"%s" "%s"', $this->findPhp(), $composerPath);
    }

    /**
     * Get the php command for the environment
     */
    protected function findPhp(): string
    {
        return (new PhpExecutableFinder)->find();
    }

    /**
     * Get the node command for the environment
     */
    protected function findNode(): string
    {
        return (new ExecutableFinder())->find('node');
    }

    /**
     * Get the roadrunner binary for the environment
     */
    protected function findRoadRunnerBinary(): ?string
    {
        if (file_exists(Environment::getProjectPath() . '/rr')) {
            return Environment::getProjectPath() . '/rr';
        }

        return (new ExecutableFinder())->find('rr', null, [Environment::getProjectPath()]);
    }
}
