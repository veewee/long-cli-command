<?php

declare(strict_types=1);

namespace App\Container;

use App\Console\Command\FileGeneratorCommand;
use App\Console\Command\TestCommand;
use App\Process\ProxyBuilder;
use App\Util\MaxCliInputLengthDetector;
use DI;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;

class ContainerFactory
{
    public static function create(): ContainerInterface
    {
        $builder = new DI\ContainerBuilder();
        $builder->addDefinitions([
            'project_path' => getenv('PROJECT_PATH'),
            'runtime_path' => DI\string('{project_path}/runtime'),
            Filesystem::class => DI\create(Filesystem::class),
            Application::class => function (ContainerInterface $container) {
                $app = new Application('long-cli', '1.0.0');
                $app->addCommands([
                    $container->get(FileGeneratorCommand::class),
                    $container->get(TestCommand::class),
                ]);

                return $app;
            },
            TestCommand::class => function (ContainerInterface $container) {
                return new TestCommand(
                    $container->get(MaxCliInputLengthDetector::class),
                    $container->get(ProxyBuilder::class),
                    (string) $container->get('runtime_path')
                );
            },
            FileGeneratorCommand::class => function (ContainerInterface $container) {
                return new FileGeneratorCommand(
                    $container->get(Filesystem::class),
                    (string) $container->get('runtime_path')
                );
            },
            MaxCliInputLengthDetector::class => DI\create(MaxCliInputLengthDetector::class),
            ProxyBuilder::class => DI\create(ProxyBuilder::class),
        ]);

        return $builder->build();
    }
}
