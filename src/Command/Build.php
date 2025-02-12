<?php

namespace PHPacker\PHPacker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'build',
    description: 'Package standalone binary'
)]
class Build extends Command
{

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello World');

        return Command::SUCCESS;
    }
}
