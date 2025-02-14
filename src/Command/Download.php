<?php

namespace PHPacker\PHPacker\Command;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Command\Concerns\WithVersions;
use Symfony\Component\Console\Output\OutputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use PHPacker\PHPacker\Command\Concerns\InteractsWithRepository;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

#[AsCommand(
    name: 'download',
    description: 'Download the latest prebuilt PHP binaries'
)]
class Download extends Command
{
    use InteractsWithRepository;
    use WithVersions;

    const DEFAULT_REPOSITORY = 'phpacker/php-bin';
    const DEFAULT_REPOSITORY_DIR = 'default';

    private string $repository;
    private string $repositoryDir;

    private ?string $currentVersion;
    private ?string $latestVersion;

    private Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem;
    }

    protected function configure(): void
    {
        $this->addArgument('repository', InputArgument::OPTIONAL, 'Target binaries repository', self::DEFAULT_REPOSITORY);
    }

    protected function download()
    {
        if (! $this->latestVersion) {
            throw new CommandErrorException("No version tagged for '{$this->repository}'");
        }

        // Nothing installed
        if (! $this->currentVersion) {
            $this->prepareDirectory();
            $this->fetchLatest();

            return;
        }

        if ($this->currentVersion === $this->latestVersion) {
            info('You already have the latest version.');

            return;
        }

        $this->fetchLatest();
    }

    protected function fetchLatest()
    {
        $this->currentVersion
            ? info("Updating {$this->repository}:{$this->currentVersion}->{$this->latestVersion}'")
            : info("Downloading {$this->repository}:{$this->latestVersion}");

        print_r($this->repositoryData($this->repository)['zipball_url']);
    }

    protected function prepareDirectory()
    {
        $this->filesystem->mkdir($this->repositoryDir);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Store in OS-specific app data directory
        $baseDir = match (PHP_OS_FAMILY) {
            'Darwin' => Path::join(getenv('HOME'), '.my-app'),
            'Windows' => Path::join(getenv('LOCALAPPDATA'), 'my-app'),
            default => Path::join(getenv('XDG_DATA_HOME') ?: Path::join(getenv('HOME'), '.my-app'))
        };

        $this->repository = $input->getArgument('repository');

        $dirName = $this->repository === self::DEFAULT_REPOSITORY
            ? self::DEFAULT_REPOSITORY_DIR
            : $this->repository;

        $this->repositoryDir = Path::join($baseDir, $dirName);
        $this->currentVersion = $this->currentVersion($this->repositoryDir);
        $this->latestVersion = $this->latestVersion($this->repository);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->download($input, $output);

            return Command::SUCCESS;
        } catch (CommandErrorException $e) {
            error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
