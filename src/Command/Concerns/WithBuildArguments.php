<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;

use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;

/**
 * @var array PLATFORMS List of supported platforms.
 */
trait WithBuildArguments
{
    /*
    * Prompt for all required inputs
    */
    protected function handleInput(InputInterface $input, array $platforms): array
    {
        // Get platform (from argument or prompt)
        $platform = $input->getArgument('platform') ?: select(
            'Select platform',
            ['mac' => 'Mac', 'linux' => 'Linux', 'windows' => 'Windows', 'all' => 'all']
        );

        // Retun all available platforms (except the 'all' special case)
        if ($platform === 'all') {
            return array_diff_key($platforms, ['all' => null]);
        }

        // Validate
        $availablePlatforms = array_keys($platforms);
        if (! in_array($platform, $availablePlatforms)) {
            throw new CommandErrorException("Invalid platform '{$platform}'. Options are: " . implode(', ', $availablePlatforms));
        }

        // Get architectures (from arguments or prompt)
        $validArchitectures = $platforms[$platform];
        $architectures = $input->getArgument('architectures') ?: multiselect(
            "Select architectures for {$platform}",
            $validArchitectures,
            required: true
        );

        // Validate combinations
        foreach ($architectures as $arch) {
            if (! in_array($arch, $validArchitectures)) {
                throw new CommandErrorException("Invalid architecture '{$arch}' for {$platform}");
            }
        }

        return [$platform => $architectures];
    }
}
