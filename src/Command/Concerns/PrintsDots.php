<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Output\OutputInterface;

trait PrintsDots
{
    private function printDots(string $text, string $append, OutputInterface $output, int $maxLength = 60)
    {
        $terminalWidth = (new Terminal)->getWidth();
        $maxLength = min($maxLength - 2, $terminalWidth - 8);

        $dots = str_repeat('·', $maxLength - strlen($text) - strlen($append));

        $output->writeln("  <options=bold>{$text}</> <fg=gray>{$dots}</> {$append}");
    }
}
