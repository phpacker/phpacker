<?php

namespace Tests\_stubs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('stub')]
class CommandDouble extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('src', 's', InputOption::VALUE_OPTIONAL)
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL)
            ->addOption('ini', 'i', InputOption::VALUE_OPTIONAL, false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
