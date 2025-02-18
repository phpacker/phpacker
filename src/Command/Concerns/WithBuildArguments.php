<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Support\Config\ConfigManager;
use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;

use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;
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
        // Get platform (from config, argument or prompt)
        $platform = ConfigManager::get('platform') ?? $input->getArgument('platform') ?: select(
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

        // Get architectures (from config, arguments or prompt)
        $validArchitectures = $platforms[$platform];
        $architectures = ConfigManager::get('architectures') ?? $input->getArgument('architectures') ?: multiselect(
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

    /*
     * All options will be automatically merged with the config
     * Only when a --ini flag was given without a value
     * we prompt with a textarea input
     */
    protected function promptIniInput(InputInterface $input)
    {
        // Already handled by ConfigManager bootstrap
        if ($input->getOption('ini') !== null) {
            return ConfigManager::get('ini');
        }

        $raw = textarea(
            'ini settings',
            required: true
        );

        $ini = parse_ini_string($raw, scanner_mode: INI_SCANNER_RAW);
        if ($ini === false) {
            throw new CommandErrorException('Invalid ini input. Please check for syntax errors');
        }

        return $ini;
    }
}
