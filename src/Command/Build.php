<?php

namespace PHPacker\PHPacker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Command\Concerns\WithIniOption;
use Symfony\Component\Console\Output\OutputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use PHPacker\PHPacker\Command\Concerns\WithBuildArguments;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

#[AsCommand(
    name: 'build',
    description: 'Package standalone executable'
)]
class Build extends Command
{
    use WithBuildArguments;
    use WithIniOption;

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
            ->addOption('ini', 'i', InputOption::VALUE_OPTIONAL, 'Path to ini file (default ./phpacker.ini)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->build($input, $output);

            return Command::SUCCESS;
        } catch (CommandErrorException $e) {
            error($e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function build(InputInterface $input, OutputInterface $output)
    {
        $targets = $this->handleInput($input, self::PLATFORMS);
        $ini = $this->determineIni($input);

        if ($ini) {
            $this->printIniTable($ini);
        }

        foreach ($targets as $platform => $archs) {
            foreach ($archs as $arch) {
                info("Building for {$platform}-{$arch}");

                // TODO: Combine self-executable with script
            }
        }
    }
}
