<?php

namespace Crazy252\Typo3Kate\Command;

use Crazy252\Typo3Kate\Server\RoadRunner\RoadRunner;
use Crazy252\Typo3Kate\Trait\Find;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StopCommand extends Command
{
    use Find;

    protected function configure(): void
    {
        $this->setDescription('Stop the TYPO3 Kate server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Stopping TYPO3 Kate...</info>');

        /** @var RoadRunner $instance */
        $instance = GeneralUtility::makeInstance(RoadRunner::class, $input, $output);
        return $instance->stop();
    }
}
