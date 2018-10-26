<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Process\ProcessBuilder;
use App\Process\ProxyBuilder;
use App\Util\MaxCliInputLengthDetector;
use PhpCsFixer\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class TestCommand extends Command
{
    /**
     * @var MaxCliInputLengthDetector
     */
    private $maxCliInputLengthDetector;

    /**
     * @var ProxyBuilder
     */
    private $proxyBuilder;

    /**
     * @var string
     */
    private $runtimePath;

    public function __construct(
        MaxCliInputLengthDetector $maxCliInputLengthDetector,
        ProxyBuilder $proxyBuilder,
        string $runtimePath
    ) {
        parent::__construct();
        $this->maxCliInputLengthDetector = $maxCliInputLengthDetector;
        $this->proxyBuilder = $proxyBuilder;
        $this->runtimePath = $runtimePath;
    }

    protected function configure(): void
    {
        $this->setName('test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $finder = $this->detectFiles();
        $style->writeln('Detected '.$finder->count().' PHP files.');

        $process = $this->buildFixerProcess($finder);
        $proxy = $this->proxyBuilder->build($process);

        $processLength = strlen($process->getCommandLine());
        $proxyLength = strlen($proxy->getCommandLine());
        $maxCliLength = $this->maxCliInputLengthDetector->detect();
        $inBetween = abs($processLength-$maxCliLength);
        $moreOrLess = ($processLength > $maxCliLength) ? 'more' : 'less';

        if ($output->isVerbose()) {
            $style->writeln('Executing CLI command:');
            $style->writeln($process->getCommandLine());
        }

        $style->writeln('Got CLI input length of: '.$processLength.' characters.');
        $style->writeln('Proxy CLI input length: '.$proxyLength.' characters.');
        $style->writeln('Maximum amount of cli input: '.$maxCliLength.' characters.');
        $style->warning('Got '.$inBetween.' characters '.$moreOrLess.' then max cli input for actual command.');

        $style->writeln('Running process in a regular way:');
        $process->run();
        $this->styleProcessOutput($style, $process);

        $style->writeln('Running process through proxy:');
        $proxy->run();
        $this->styleProcessOutput($style, $proxy);


        $commandExecuted = $process->getExitCode() === 8 || $proxy->getExitCode() === 8;
        if (!$commandExecuted) {
            $style->error('Test command failed in both process and proxy.');
            return 1;
        }

        $style->success('Hooray!');
        return 0;
    }

    private function stripOuput(string $output): string
    {
        $lines = preg_split('{[\r\n]+}', $output);
        if (count($lines) <= 10) {
            return $output;
        }

        $stripped = array_merge(
            array_slice($lines, 0, 5),
            ['....'],
            array_slice($lines, -5, 5)
        );

        return implode(PHP_EOL, $stripped);
    }

    private function detectFiles(): Finder
    {
        return Finder::create()
            ->in($this->runtimePath)
            ->files()
            ->name('*.php');
    }

    private function buildFixerProcess(Finder $finder): Process
    {
        return ProcessBuilder::createForExecutable('php-cs-fixer')
            ->addArgument('fix')
            ->addArgument('--dry-run')
            ->addArgument('--config=.php_cs.dist')
            ->addArgument('--')
            ->addArguments(array_map(
                function(\SplFileInfo $file): string {
                    return $file->getPathname();
                },
                iterator_to_array($finder)
            ))
            ->build();
    }

    private function styleProcessOutput(SymfonyStyle $style, Process $process)
    {
        $style->listing([
            'exit code:' . $process->getExitCode(),
            'output:' . $this->stripOuput($process->getOutput()),
            'error output'.$process->getErrorOutput(),
        ]);
    }
}
