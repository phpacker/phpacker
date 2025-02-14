<?php

namespace PHPacker\PHPacker\Command;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use PHPacker\PHPacker\Exceptions\RepositoryRequestException;
use PHPacker\PHPacker\Command\Concerns\InteractsWithRepository;
use PHPacker\PHPacker\Command\Concerns\InteractsWithAssetManager;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\error;

#[AsCommand(
    name: 'download',
    description: 'Download the latest prebuilt PHP binaries'
)]
class Download extends Command
{
    use InteractsWithAssetManager;
    use InteractsWithRepository;

    const DEFAULT_REPOSITORY = 'phpacker/php-bin';
    const DEFAULT_REPOSITORY_DIR = 'default';

    private string $repository;
    private string $repositoryDir;

    private ?string $currentVersion;
    private ?string $latestVersion;

    protected function configure(): void
    {
        $this
            ->addArgument('repository', InputArgument::OPTIONAL, 'Target binaries repository', self::DEFAULT_REPOSITORY)
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force fetch a fresh copy of the binaries', false);
    }

    protected function download(InputInterface $input)
    {
        if (! $this->latestVersion) {
            throw new CommandErrorException("No version tagged for '{$this->repository}'");
        }

        $this->assetManager()->prepareDirectory();

        // Always download when forcing
        if ($input->hasParameterOption(['--force', '-f'])) {
            return $this->fetchLatest();
        }

        // Nothing installed
        if (! $this->currentVersion) {
            return $this->fetchLatest();
        }

        if ($this->currentVersion === $this->latestVersion) {
            info('You already have the latest version.');

            return;
        }

        // TODO: Actually handle version strings? We only check if current version matches right now
        $this->fetchLatest();
    }

    protected function fetchLatest()
    {
        $releaseData = $this->repository()->releaseData();

        if (! isset($releaseData['assets'])) {
            throw new CommandErrorException("No assets found in the release.\n");
        }

        $message = $this->currentVersion
            ? "Updating {$this->repository}:{$this->currentVersion} -> {$this->latestVersion}"
            : "Downloading {$this->repository}:{$this->latestVersion}";

        spin(
            message: $message,
            callback: function () {
                $zipPath = $this->repository()->downloadReleaseAssets($this->repositoryDir);
                $this->assetManager()->unpack($zipPath, $this->latestVersion);
            }
        );

        info("Extracted {$this->repository}:{$this->latestVersion}");
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Store in OS-specific app data directory
        $baseDir = match (PHP_OS_FAMILY) {
            'Darwin' => Path::join(getenv('HOME'), '.phpacker'),
            'Windows' => Path::join(getenv('LOCALAPPDATA'), 'phpacker'),
            default => Path::join(getenv('XDG_DATA_HOME') ?: Path::join(getenv('HOME'), '.phpacker'))
        };

        $this->repository = $input->getArgument('repository');

        $dirName = $this->repository === self::DEFAULT_REPOSITORY
            ? self::DEFAULT_REPOSITORY_DIR
            : str_replace(['/', '\\'], '-', $this->repository);

        $this->repositoryDir = Path::join($baseDir, 'binaries', $dirName);
        $this->currentVersion = $this->assetManager()->currentVersion();
        $this->latestVersion = $this->repository()->latestVersion();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->download($input);

            return Command::SUCCESS;
        } catch (CommandErrorException|RepositoryRequestException $e) {
            error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
