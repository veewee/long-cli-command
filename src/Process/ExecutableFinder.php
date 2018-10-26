<?php

declare(strict_types=1);

namespace App\Process;

use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

class ExecutableFinder
{
    public static function find(string $program): string
    {
        self::ensureProjectBinDirInSystemPath(getenv('PROJECT_PATH').'/vendor/bin');

        $executable = (new SymfonyExecutableFinder())->find($program);
        if (!$executable) {
            throw new \RuntimeException('Could not find program: '.$program);
        }

        return $executable;
    }

    private static function ensureProjectBinDirInSystemPath($binDir)
    {
        $pathStr = 'PATH';
        if (!isset($_SERVER[$pathStr]) && isset($_SERVER['Path'])) {
            $pathStr = 'Path';
        }

        if (!is_dir($binDir)) {
            return;
        }

        // add the bin dir to the PATH to make local binaries of deps usable in scripts
        $binDir = realpath($binDir);
        $hasBindDirInPath = preg_match(
            '{(^|' . PATH_SEPARATOR . ')' . preg_quote($binDir) . '($|' . PATH_SEPARATOR . ')}',
            $_SERVER[$pathStr]
        );

        if (!$hasBindDirInPath && isset($_SERVER[$pathStr])) {
            $_SERVER[$pathStr] = $binDir . PATH_SEPARATOR . getenv($pathStr);
            putenv($pathStr . '=' . $_SERVER[$pathStr]);
        }
    }
}
