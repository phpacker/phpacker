<?php

namespace PHPacker\PHPacker\Support;

use Laravel\Prompts\SelectPrompt;
use Laravel\Prompts\TextareaPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\Prompt as LaravelPrompt;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Prompt
{
    protected static bool $loaded = false;

    public static function bootstrap(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        LaravelPrompt::setOutput($output);

        LaravelPrompt::fallbackWhen(PHP_OS_FAMILY === 'Windows');
        // LaravelPrompt::fallbackWhen(true);

        LaravelPrompt::interactive($input->isInteractive() && defined('STDIN') && stream_isatty(STDIN));

        TextareaPrompt::fallbackUsing(
            fn (TextareaPrompt $prompt) => self::textareaFallback($prompt, $style)
        );

        SelectPrompt::fallbackUsing(
            fn (SelectPrompt $prompt) => self::selectFallback($prompt, $style)
        );

        MultiSelectPrompt::fallbackUsing(
            fn (MultiSelectPrompt $prompt) => self::multiSelectFallback($prompt, $style)
        );

    }

    protected static function textareaFallback(TextareaPrompt $prompt, SymfonyStyle $style)
    {
        $inputLines = [];

        while (true) {
            $line = $style->ask(
                $prompt->label . ' (END to exit)',
                default: ''
            );

            if (trim($line) === 'END') {
                break;
            }

            $inputLines[] = $line;
        }

        return implode(PHP_EOL, $inputLines);
    }

    protected static function selectFallback(SelectPrompt $prompt, SymfonyStyle $style)
    {
        $options = array_keys($prompt->options);

        return $style->choice(
            $prompt->label,
            $options,
        );
    }

    protected static function multiSelectFallback(MultiSelectPrompt $prompt, SymfonyStyle $style)
    {
        $options = array_values($prompt->options);

        return $style->choice(
            $prompt->label,
            $options,
            multiSelect: true
        );
    }
}
