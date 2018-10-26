<?php

declare(strict_types=1);

namespace App\Process;

use App\Util\Platform;
use Symfony\Component\Process\Process;

class ProxyBuilder
{
    private $tempFiles = [];

    public function build(Process $process): Process
    {
        $tmpFile = $this->writeCommandToTempFile($process);
        return Platform::isWindows()
            ? $this->buildForWindows($tmpFile)
            : $this->buildForUnix($tmpFile);
    }

    private function buildForWindows(string $tmpFile): Process
    {
        return ProcessBuilder::createForExecutable('cmd.exe')
            ->addArgument('/C')
            ->addArgument($tmpFile)
            ->build();
    }

    private function buildForUnix(string $tmpFile): Process
    {
        return ProcessBuilder::createForExecutable('sh')
            ->addArgument($tmpFile)
            ->build();
    }

    private function writeCommandToTempFile(Process $process): string
    {
        $this->tempFiles[] = $file = tempnam(sys_get_temp_dir(), 'long-cli-command');
        file_put_contents($file, $process->getCommandLine());

        return $file;
    }


    public function __destruct()
    {
        foreach ($this->tempFiles as $tempFile) {
            @unlink($tempFile);
        }
    }
}
