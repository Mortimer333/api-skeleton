<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Util\BinUtilService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommandAbstract extends Command
{
    /** @var SymfonyStyle */
    protected ?SymfonyStyle $io = null;

    /** @var array<array<string, string>> $warnings Warnings to display at the end of process */
    protected array $warnings = [];

    /** @var array<array<string, string>> $errors Errors to display at the end of process */
    protected array $errors = [];

    /** @var array<array<string, string>> $infos Additional information to display at the end of process */
    protected array $infos = [];

    /** @var int The amount of indent */
    protected int $indent = 0;

    /** @var bool Decides if to show any output to console */
    protected bool $outputEnabled = true;

    /** @var string|null Microtime of when script started running */
    private ?string $timeStart = null;

    protected bool $save = false;
    protected ?string $logPath = null;

    /** @var resource|false $log */
    protected $log = false;

    protected LoggerInterface $logger;
    protected BinUtilService $binUtilService;

    protected function configure(): void
    {
        parent::configure();
        $this->addOption(
            'run',
            null,
            InputOption::VALUE_REQUIRED,
            'DeployActionRun ID'
        );
    }

    public function presentReport(): void
    {
        $endReport = $this->getEndReport();

        $this->log('Command has finished. ' . $endReport);

        if (\sizeof($this->warnings) > 0) {
            $this->log('Displaying warnings:');
            $this->increaseIndent();
            foreach ($this->warnings as $i => $warning) {
                $this->logError('[WARNING] ' . ($i + 1) . ':', $warning);
            }
            $this->decreaseIndent();
            $this->log('============');
        }

        if (\sizeof($this->infos) > 0) {
            $this->log('Displaying announcements:');
            $this->increaseIndent();
            foreach ($this->infos as $i => $info) {
                $this->logError('[INFO] ' . ($i + 1) . ':', $info);
            }
            $this->decreaseIndent();
            $this->log('============');
        }

        if (\sizeof($this->errors) > 0) {
            $this->log('Displaying errors:');
            $this->increaseIndent();
            foreach ($this->errors as $i => $error) {
                $this->logError('[ERROR] ' . ($i + 1) . ':', $error);
            }
            $this->decreaseIndent();
            $this->log('============');
        }
    }

    public function disableOutput(): void
    {
        $this->outputEnabled = false;
    }

    public function enableOutput(): void
    {
        $this->outputEnabled = true;
    }

    protected function increaseIndent(): self
    {
        ++$this->indent;

        return $this;
    }

    protected function decreaseIndent(): self
    {
        --$this->indent;

        return $this;
    }

    protected function setIndent(int $indent): self
    {
        $this->indent = $indent;

        return $this;
    }

    protected function getIndent(): int
    {
        return $this->indent;
    }

    /**
     * One method to display message in log file and in console.
     */
    protected function log(string $message): void
    {
        $time = date('Y-m-d H:i:s ');
        $message = ltrim(trim($message), $time);
        $message = $time . str_repeat('    ', $this->indent) . $message;
        if ($this->outputEnabled && isset($this->io)) {
            $this->io->writeln($message);
        } else {
            $this->logger->info($message);
        }

        if ($this->save) {
            $this->saveOutput($message);
        }
    }

    protected function saveOutput(string $message): void
    {
        if (!$this->logPath) {
            $this->logPath = BinUtilService::getRootPath() . '/var/command/' . date('Y') . '/' . date('m') . '/'
                . date('d') . '/';
            if (!is_dir($this->logPath)) {
                mkdir($this->logPath, 0755, true);
            }

            $this->logPath .= $this->binUtilService->normalizeName($this->getName() ?? '') . '_' . getmypid() . '.log';
            $this->log = fopen($this->logPath, 'w');
        }

        if (!$this->log) {
            throw new \Exception("Couldn't open log file");
        }

        fwrite($this->log, $message . PHP_EOL);
    }

    /**
     * @param \Throwable|string|array<string, string> $error
     */
    protected function addError(string $title, \Throwable|string|array $error = []): self
    {
        $error = $this->addTitleToError($title, $this->createError($error));
        $this->errors[] = $error;
        $this->logError('Error log:', $error);

        return $this;
    }

    /**
     * @param \Throwable|string|array<string, string> $warning
     */
    protected function addWarning(string $title, \Throwable|string|array $warning = []): self
    {
        $warning = $this->addTitleToError($title, $this->createError($warning));
        $this->warnings[] = $warning;
        $this->logError('Warning log:', $warning);

        return $this;
    }

    /**
     * @param \Throwable|string|array<string, string> $info
     */
    protected function addInfo(string $title, \Throwable|string|array $info = []): self
    {
        $info = $this->addTitleToError($title, $this->createError($info));
        $this->infos[] = $info;
        $this->logError('Info log:', $info);

        return $this;
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->timeStart = \microtime();
        try {
            $this->command($input, $output);
            $returnValue = $this->onSuccess($input, $output);
        } catch (\Throwable $e) {
            $returnValue = $this->onFailure($input, $output);
            if (self::FAILURE === $returnValue) {
                $this->addError('Unexpected Error', $e);
                $this->binUtilService->saveLastErrorTrace($e);
            }
        }

        $this->presentReport();
        $this->displayTimeAndMemory();

        if ($this->log) {
            fclose($this->log);
        }

        return $returnValue;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function onFailure(InputInterface $input, OutputInterface $output): int
    {
        return self::FAILURE;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function onSuccess(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    protected function exceptionToArray(\Throwable $e): array
    {
        return [
            'file' => $e->getFile(),
            'line' => (string) $e->getLine(),
            'message' => $e->getMessage(),
        ];
    }

    abstract protected function command(InputInterface $input, OutputInterface $output): void;

    private function getMilliseconds(string $microtime): float
    {
        $mt = explode(' ', $microtime);
        $seconds = (int) $mt[1];
        $mili = (int) $mt[0];

        return ($seconds * 1000) + round($mili * 1000);
    }

    private function timerEnd(): float
    {
        $timeEnd = $this->getMilliseconds(microtime());

        if (\is_null($this->timeStart)) {
            throw new \Exception('Time start is null', 400);
        }

        $timeStart = $this->getMilliseconds($this->timeStart);
        $time = $timeEnd - $timeStart;

        return $time / 1000;
    }

    /**
     * @param array<string, string> $error
     */
    private function logError(string $title, array $error): void
    {
        $this->log($title);
        $this->increaseIndent();
        foreach ($error as $key => $value) {
            if (strpos($value, "\n")) {
                $this->log("$key: ");
                $this->increaseIndent();
                foreach (explode("\n", $value) as $line) {
                    $this->log($line);
                }
                $this->decreaseIndent();
                continue;
            }
            $this->log("$key: $value");
        }
        $this->decreaseIndent();
    }

    /**
     * @param array<string, string> $error
     *
     * @return array<string, string>
     */
    private function addTitleToError(string $title, array $error): array
    {
        if (isset($error['title'])) {
            $error['title'] .= ';' . PHP_EOL;
        } else {
            $error['title'] = '';
        }

        $error['title'] .= $title;

        return $error;
    }

    private function getEndReport(): string
    {
        return 'Errors: ' . \sizeof($this->errors)
            . ', Warnings: ' . \sizeof($this->warnings)
            . ', Notifications: ' . \sizeof($this->infos);
    }

    private function displayTimeAndMemory(): void
    {
        if (isset($this->io)) {
            $time = $this->timerEnd();
            $this->io->comment(
                sprintf(
                    'Process took %s seconds and used %s memory',
                    $time,
                    (function () {
                        $size = \memory_get_usage(true);
                        $unit = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];

                        return \round($size / \pow(1024, $i = floor(log($size, 1024))), 2) . ' ' . $unit[$i];
                    })()
                )
            );
        }
    }

    /**
     * @param \Throwable|string|array<string, string> $error
     *
     * @return array<string, string>
     */
    private function createError(\Throwable|string|array $error): array
    {
        if ($error instanceof \Throwable) {
            $error = $this->exceptionToArray($error);
        } elseif (is_string($error)) {
            $error = [
                'message' => $error,
            ];
        }

        return $error;
    }
}
