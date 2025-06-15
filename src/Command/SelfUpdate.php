<?php

namespace PHPacker\PHPacker\Command;

use PHPacker\Updater\Updater\UpdateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\note;
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
        // $currentVersion = 'v0.3';
        $currentVersion = $this->getApplication()->getVersion();
        // @phpstan-ignore class.notFound
        $updateManager = UpdateManager::make($currentVersion, __DIR__ . '/../../phpacker.json');

        $updateData = $updateManager->check();

        if (! $updateData->updateAvailable) {
            note("You're on the latest version {$updateData->currentVersion}");

            return Command::SUCCESS;
        }

        note("Update available! Do you want to update PHPacker {$updateData->currentVersion} -> {$updateData->latestVersion} ?");
        pause('Press ENTER to continue.');

        $updateManager->update();

        return Command::SUCCESS;
    }
}
