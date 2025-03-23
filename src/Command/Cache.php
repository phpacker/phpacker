<?php

namespace PHPacker\PHPacker\Command;

use Symfony\Component\Filesystem\Path;
use PHPacker\PHPacker\Support\AssetManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use PHPacker\PHPacker\Command\Concerns\PrintsDots;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use PHPacker\PHPacker\Command\Concerns\InteractsWithAssetManager;
use Symfony\Component\Console\Exception\InvalidArgumentException;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

#[AsCommand(
    name: 'cache',
    description: 'Manage your downloaded PHP binaries'
)]
class Cache extends Command
{
    use InteractsWithAssetManager;
    use PrintsDots;

    private string $repository;

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action to perform (list or clear)', default: 'list')
            ->addArgument('repository', InputArgument::OPTIONAL, 'Target binaries repository', AssetManager::DEFAULT_REPOSITORY);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->repository = $input->getArgument('repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            match ($input->getArgument('action')) {
                'list' => $this->info($input, $output),
                'clear' => $this->clear($input, $output),
                default => throw new InvalidArgumentException("You may only call 'info' or 'clear'")
            };

            return Command::SUCCESS;
        } catch (CommandErrorException $e) {
            error($e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function info(InputInterface $input, OutputInterface $output)
    {
        $cachePath = Path::join(APP_DATA, 'binaries');
        $directories = array_filter(glob($cachePath . '/*'), 'is_dir');

        foreach ($directories as $directory) {

            $directoryName = basename($directory);
            $version = @file_get_contents(Path::join($directory, '_version'));

            if (empty($version)) {
                $version = 'N/A';
            }

            $this->printDots($directoryName, $version, $output);
        }

        if (empty($directories)) {
            info('No binary downloads cached.');
        }
    }

    protected function clear(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem;
        $cachePath = Path::join(APP_DATA, 'binaries');

        // If no specific repository was given, clear everything
        if (! $this->repository) {

            $directories = array_filter(glob($cachePath . '/*'), 'is_dir');

            foreach ($directories as $directory) {
                $filesystem->remove($directory);
            }

            info('Cleared all binary downloads.');

            return;
        }

        $repositoryDir = $this->assetManager()->baseDir();

        if (! is_dir($repositoryDir)) {
            throw new CommandErrorException("{$this->repository} is not installed");
        }

        $filesystem->remove($repositoryDir);
        info("Cleared {$this->repository} binary downloads.");
    }
}
