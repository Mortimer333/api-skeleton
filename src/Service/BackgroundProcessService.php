<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Util\BinUtilService;
use Symfony\Component\Process\Process;

class BackgroundProcessService
{
    public function __construct(
        protected BinUtilService $binUtilService,
    ) {
    }

    public function getLogFolderPath(): string
    {
        return $this->binUtilService->getRootPath() . '/var/processes/';
    }

    /**
     * @param array<string, string|int|float> $arguments
     */
    public function create(string $command, array $arguments = []): Process
    {
        $command .= $this->transformArguments($arguments);
        $process = Process::fromShellCommandline(
            $command,
            $this->binUtilService->getRootPath(),
            [
                // Setting this to nothing actually makes environment go through making background command tests
                // achievable
                'SYMFONY_DOTENV_VARS' => false,
            ]
        );
        $process->setOptions(['create_new_console' => true]);

        return $process;
    }

    /**
     * @param array<string, string|int|float|array<int, string|int|float>> $args
     */
    public static function transformArguments(array $args): string
    {
        $argsStr = '';
        foreach ($args as $key => $value) {
            $key = \strlen($key) > 1 ? '-' . $key . ' ' : $key;
            if (\is_array($value)) {
                foreach ($value as $argument) {
                    $argsStr .= rtrim(' -' . $key . $argument);
                }
            } else {
                $argsStr .= rtrim(' -' . $key . $value);
            }
        }

        return $argsStr;
    }
}
