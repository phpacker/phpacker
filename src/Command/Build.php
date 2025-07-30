<?php

namespace PHPacker\PHPacker\Command;

use PHPacker\PHPacker\Support\Combine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use PHPacker\PHPacker\Command\Concerns\PrintsDots;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use PHPacker\PHPacker\Support\Config\ConfigManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPacker\PHPacker\Exceptions\CombineErrorException;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use PHPacker\PHPacker\Command\Concerns\WithBuildArguments;

use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

#[AsCommand(
    name: 'build',
    description: 'Package standalone executable'
)]
class Build extends Command
{
    use PrintsDots;
    use WithBuildArguments;

    private const PLATFORMS = [
        'mac' => ['arm', 'x64'],
        'linux' => ['arm', 'x64'],
        'windows' => ['x64'],
        'all' => null, // Special case
    ];

    protected function configure(): void
    {
        $this
            ->addArgument('platform', InputArgument::OPTIONAL, 'Target platform')
            ->addArgument('architectures', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Target architectures')
            ->addOption('src', 's', InputOption::VALUE_REQUIRED, 'Path to the target php or phar file')
            ->addOption('dest', 'd', InputOption::VALUE_REQUIRED, 'Path to the build directory')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file (default: {src-dir}/phpacker.json)')
            ->addOption('ini', 'i', InputOption::VALUE_OPTIONAL, 'Path to ini file (default: {src-dir}/phpacker.ini)', false)
            ->addOption('php', 'p', InputOption::VALUE_OPTIONAL, 'PHP version', ConfigManager::get('php'))
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force fetch a fresh copy of the binaries', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Fetch latest binaries
            $downloadExitCode = $this->getApplication()->doRun(new ArrayInput([
                'command' => 'download',
                'repository' => ConfigManager::get('repository'),
                $input->hasParameterOption(['--force', '-f']) ? '--force' : '',
                $input->hasParameterOption(['--quiet', '-q']) ? '--quiet' : '',
            ]), $output);

            if ($downloadExitCode === Command::FAILURE) {
                return Command::FAILURE;
            }

            $this->build($input, $output);

            return Command::SUCCESS;
        } catch (CommandErrorException|CombineErrorException $e) {
            error($e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function build(InputInterface $input, OutputInterface $output)
    {
        $targets = $this->handleInput($input, $output, self::PLATFORMS);

        $input->validate();

        $this->validateSrcPath();

        if ($ini = ConfigManager::get('ini')) {
            $this->printIniTable($ini);
        }

        foreach ($targets as $platform => $archs) {
            foreach ($archs as $arch) {
                // TODO: Extract Combine args to DTO? The config is dynamic and can
                Combine::build($platform, $arch, ConfigManager::getRepository());
                $this->printDots("{$platform} - {$arch}", '✅', $output);
            }
        }

        $output->write(PHP_EOL);
    }

    private function printIniTable(array $ini)
    {
        $rows = array_map(function ($value, $key) {
            return [$key, $value];
        }, $ini, array_keys($ini));

        table(['  directive  ', '    value    '], $rows);
    }
}
