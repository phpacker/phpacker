<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Support\Prompt;
use PHPacker\PHPacker\Support\Config\ConfigManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;

use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\multiselect;

/**
 * @var array PLATFORMS List of supported platforms.
 */
trait WithBuildArguments
{
    const PHP_VERSIONS = ['8.2', '8.3', '8.4'];

    /*
    * Prompt for all required inputs
    */
    protected function handleInput(InputInterface $input, OutputInterface $output, array $platforms): array
    {
        Prompt::bootstrap($input, $output);

        // Set src relative to cwd when given
        if ($src = $input->getOption('src')) {
            ConfigManager::set('src', $src);
        }

        // No src set either from config or input
        if (! ConfigManager::get('src')) {
            throw new CommandErrorException('The --src option is required.');
        }

        // Set dest relative to cwd when given
        if ($dest = $input->getOption('dest')) {
            ConfigManager::set('dest', $dest);
        }

        // Prompt for INI if needed
        if ($ini = $this->promptIniInput($input)) {
            ConfigManager::set('ini', $ini);
        }

        // Set php version (from config, argument or prompt)
        if (! $php = $input->getOption('php')) {

            $php = select(
                'Select PHP version',
                self::PHP_VERSIONS
            );
        }

        ConfigManager::set('php', $php);

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

    private function validateSrcPath()
    {
        $path = ConfigManager::get('src');

        if (! file_exists($path)) {
            throw new CommandErrorException("Source file not found: {$path}");
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (! in_array($ext, ['php', 'phar'])) {
            throw new CommandErrorException("Invalid file type: {$path}. Expected a PHP or PHAR file.");
        }
    }
}
