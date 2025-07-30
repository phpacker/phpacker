<?php

namespace PHPacker\PHPacker\Command;

use PHPacker\Publisher\Updater\UpdateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\pause;

#[AsCommand(
    name: 'self-update',
    description: 'Update PHPacker'
)]
class SelfUpdate extends Command
{
    protected function configure(): void
    {
        //
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @phpstan-ignore-next-line */
        $updateManager = UpdateManager::make(__DIR__ . '/../../phpacker.json');
        $updateData = $updateManager->check();

        if (! $updateData->updateAvailable) {
            note("You're on the latest version {$updateData->currentVersion}");

            return Command::SUCCESS;
        }

        if ($input->isInteractive()) {
            note("Update available! Do you want to update PHPacker {$updateData->currentVersion} -> {$updateData->latestVersion} ?");
            pause('Press ENTER to continue.');
        }

        // TODO: Consider udating the php binaries binaries too

        spin(
            fn () => $updateManager->update(),
            "Installing update {$updateData->latestVersion}"
        );

        info('Update installed successfully!');

        return Command::SUCCESS;
    }
}
