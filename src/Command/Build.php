<?php

namespace PHPacker\PHPacker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;

#[AsCommand(
    name: 'build',
    description: 'Package standalone binary'
)]
class Build extends Command
{
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
            ->addArgument('architectures', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Target architectures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targets = $this->handleInput($input);

        foreach ($targets as $platform => $archs) {
            foreach ($archs as $arch) {
                $output->writeln("Building for {$platform}-{$arch}");
            }
        }

        return Command::SUCCESS;
    }

    protected function handleInput(InputInterface $input): array
    {
        // Get platform (from argument or prompt)
        $platform = $input->getArgument('platform') ?: select(
            'Select platform',
            ['mac' => 'Mac', 'linux' => 'Linux', 'windows' => 'Windows', 'all' => 'all']
        );

        // Retrun all available platforms (except the 'all' special case)
        if ($platform === 'all') {
            return array_diff_key(self::PLATFORMS, ['all' => null]);
        }

        // Get architectures (from arguments or prompt)
        $validArchitectures = self::PLATFORMS[$platform];
        $architectures = $input->getArgument('architectures') ?: multiselect(
            "Select architectures for {$platform}",
            $validArchitectures,
            required: true
        );

        // Validate combinations
        foreach ($architectures as $arch) {
            if (! in_array($arch, $validArchitectures)) {
                error("Invalid architecture '{$arch}' for {$platform}");

                return Command::FAILURE;
            }
        }

        return [$platform => $architectures];
    }
}
