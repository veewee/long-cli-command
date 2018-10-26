<?php

declare(strict_types=1);

namespace App\Console\Command;

use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FileGeneratorCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $runtimePath;

    public function __construct(Filesystem $filesystem, string $runtimePath)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->runtimePath = $runtimePath;
    }

    protected function configure(): void
    {
        $this->setName('generate-files');
        $this->addArgument('fileCount', InputArgument::OPTIONAL, 'Generates random php files', 200);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $fileCount = (int) $input->getArgument('fileCount');
        $this->filesystem->remove($this->runtimePath);
        for ($i = 0; $i < $fileCount; ++$i) {
            $file = $this->runtimePath.'/'.Uuid::uuid4()->toString().'.php';
            $content = '<?php echo "Hello world";';
            $this->filesystem->dumpFile($file, $content);
        }

        $style->success('Hooray! Generated '.$fileCount.' files in '.$this->runtimePath);

        return 0;
    }
}
