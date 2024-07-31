<?php

namespace Crazy252\Typo3Kate\Command;

use Crazy252\Typo3Kate\Server\RoadRunner\RoadRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('This will install TYPO3 Kate within your project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Installing TYPO3 Kate...</info>');

        /** @var RoadRunner $server */
        $server = GeneralUtility::makeInstance(RoadRunner::class, $input, $output);
        $server->install();

        $output->writeln('<info>TYPO3 Kate installed successfully!</info>');

        return self::SUCCESS;
    }
}
