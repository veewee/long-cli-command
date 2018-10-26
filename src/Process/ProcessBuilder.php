<?php

declare(strict_types=1);

namespace App\Process;

use Symfony\Component\Process\Process;

class ProcessBuilder
{
    /**
     * @var string[]
     */
    private $arguments = [];

    private function __construct($arguments)
    {
        $this->arguments = $arguments;
    }

    public static function createForExecutable(string $executable)
    {
        return new self([
            ExecutableFinder::find($executable)
        ]);
    }

    public function addArgument(string $argument): self
    {
        $this->arguments[] = $argument;
        return $this;
    }

    public function addArguments(array $arguments): self
    {
        foreach ($arguments as $argument) {
            $this->addArgument($argument);
        }

        return $this;
    }

    public function build(): \Symfony\Component\Process\Process
    {
        $process = new Process($this->arguments);
        $process->setTimeout(500);

        return $process;
    }
}
