<?php

namespace Crazy252\Typo3Kate\Server\RoadRunner;

use Crazy252\Typo3Kate\Server\Server;
use Crazy252\Typo3Kate\Server\ServerInterface;
use Crazy252\Typo3Kate\Utility\ArrayUtility;
use RuntimeException;
use Spiral\RoadRunner\Http\PSR7Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process as SymfonyProcess;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RoadRunner extends Server implements ServerInterface
{
    /** @var string */
    const PACKAGE_HTTP = 'spiral/roadrunner-http';
    /** @var string */
    const PACKAGE_CLI = 'spiral/roadrunner-cli';

    /** @var string */
    const FILE_CONFIG = '.rr.yaml';
    /** @var string */
    const FILE_WORKER = 'roadrunner-worker.php';

    /** @var Context */
    protected $context;
    /** @var mixed */
    protected $config;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);

        $this->context = GeneralUtility::makeInstance(Context::class);
    }

    public function install(): void
    {
        if ($this->isPackageInstalled()) {
            return;
        }

        $this->installPackages();

        if ($this->findRoadRunnerBinary() === null) {
            $this->downloadBinary();
        }

        $this->copyConfig();
        //$this->copyWorker();
    }

    public function start(): int
    {
        if (!$this->isPackageInstalled()) {
            $this->output->writeln('<error>RoadRunner not installed</error>');
            return Command::FAILURE;
        }

        $roadRunnerBinary = $this->findRoadRunnerBinary();
        if ($roadRunnerBinary === null) {
            $this->output->writeln('<error>RoadRunner binary not found</error>');
            return Command::FAILURE;
        }

        $this->config = Yaml::parseFile($this->configPath());

        $this->writeContextState();

        $process = new SymfonyProcess(
            [$roadRunnerBinary, 'serve', '-c', $this->configPath()],
            Environment::getProjectPath() . '/',
            ['TYPO3_CONTEXT' => Environment::getContext(), 'TYPO3_KATE' => 1]
        );

        $process->start();

        $this->context->writeProcessId($process->getPid());

        return $this->run($process);
    }

    public function reload(): int
    {
        /** @var Process $process */
        $process = GeneralUtility::makeInstance(Process::class, $this->context);
        return $process->reload() ? Command::SUCCESS : Command::FAILURE;
    }

    public function stop(): int
    {
        /** @var Process $process */
        $process = GeneralUtility::makeInstance(Process::class, $this->context);
        return $process->stop() ? Command::SUCCESS : Command::FAILURE;
    }

    protected function isPackageInstalled(): bool
    {
        return class_exists(PSR7Worker::class);
    }

    protected function installPackages(): void
    {
        $this->output->writeln(
            sprintf('<info>TYPO3 Kate installs "%s" and "%s"</info>', self::PACKAGE_HTTP, self::PACKAGE_CLI)
        );

        $command = $this->findComposer() . sprintf('require %s %s --with-all-dependencies', self::PACKAGE_HTTP, self::PACKAGE_CLI);

        $process = SymfonyProcess::fromShellCommandline($command);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('Warning: '.$e->getMessage());
            }
        }

        try {
            $process->run(function ($type, $line) {
                $this->output->write($line);
            });
        } catch (ProcessSignaledException $e) {
            if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
                throw $e;
            }
        }
    }

    protected function copyConfig(): void
    {
        $source = ExtensionManagementUtility::extPath('typo3_kate', 'Resources/Private/RoadRunner/' . self::FILE_CONFIG);
        $target = Environment::getProjectPath() . '/' . self::FILE_CONFIG;

        $this->copyFile($source, $target);
    }

    protected function copyWorker(): void
    {
        $source = ExtensionManagementUtility::extPath('typo3_kate', 'bin/' . self::FILE_WORKER);
        $target = Environment::getPublicPath() . '/' . self::FILE_WORKER;

        $this->copyFile($source, $target);
    }

    protected function downloadBinary(): void
    {
        $command = [$this->findPhp(), './vendor/bin/rr', 'get-binary', '-n', '--ansi'];
        (new SymfonyProcess($command, Environment::getProjectPath()))
            ->run(function (string $type, string $line) {
                $this->output->write($line);
            });

        chmod(Environment::getProjectPath() . '/rr', 0755);
    }

    protected function configPath(): string
    {
        $path = $this->input->getOption('rr-config');
        if (!is_string($path)) {
            return Environment::getProjectPath() . '/' . self::FILE_CONFIG;
        }
        if ($path and !realpath($path)) {
            throw new \Exception('Unable to locate configuration file');
        }

        return realpath($path);
    }

    protected function writeContextState(): void
    {
        $this->context->writeState([
            'address' => ArrayUtility::get($this->config, 'http.address'),
            'rpc' => ArrayUtility::get($this->config, 'rpc.listen'),
            'workers' => ArrayUtility::get($this->config, 'http.pool.num_workers'),
            'requests' => ArrayUtility::get($this->config, 'http.pool.max_jobs'),
            'config' => $this->config ?? null,
        ]);
    }

    protected function writeServerOutput(SymfonyProcess $server): void
    {
        [$output, $errorOutput] = $this->getServerOutput($server);

        $output = explode("\n", $output);
        $output = array_filter($output);

        foreach ($output as $line) {
            $debug = json_decode($line, true);
            if (!is_array($debug)) {
                $this->info($line);
                continue;
            }

            $stream = json_decode($debug['msg'], true);
            if (is_array($stream)) {
                $this->raw('TODO: handle stream...');
                continue;
            }
            if ($debug['logger'] == 'server') {
                $this->raw($debug['msg']);
                continue;
            }
            if ($debug['level'] == 'INFO' and $debug['msg'] == 'http log' and isset($debug['remote_address']) and isset($debug['msg'])) {
                $this->raw(
                    $this->requestInfoMessage(
                        (int) $debug['status'],
                        $debug['method'],
                        $debug['URI'],
                        $debug['write_bytes'],
                        $debug['elapsed']
                    )
                );
            }
        }

        $errorOutput = explode("\n", $errorOutput);
        $errorOutput = array_filter($errorOutput);

        foreach ($errorOutput as $line) {
            if (strpos($line, 'DEBUG') === false || strpos($line, 'INFO') === false || strpos($line, 'WARN') === false) {
                $this->error($line);
            }
        }
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
        if (filter_var($time, FILTER_VALIDATE_INT) !== false) {
            return $time . 'ms';
        }
        return ($time * 1000) . 'ms';
    }
}
