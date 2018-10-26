<?php

declare(strict_types=1);

namespace App\Util;

use App\Process\ProcessBuilder;

class MaxCliInputLengthDetector
{
    public function detect(): int
    {
        if (Platform::isWindows()) {
            // TODO : is there a way to fetch this from the OS instead of hardcoding?
            return Platform::WINDOWS_COMMANDLINE_STRING_LIMITATION;
        }

        return $this->detectUnix();
    }

    private function detectUnix(): int
    {
        $process = ProcessBuilder::createForExecutable('getconf')->addArgument('ARG_MAX')->build();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Could not detect max input size: '.$process->getErrorOutput());
        }

        return (int)$process->getOutput();
    }
}
