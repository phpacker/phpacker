<?php

use Tests\_stubs\CommandDouble;
use PHPacker\PHPacker\Command\Build;
use Symfony\Component\Process\Process;
use PHPacker\PHPacker\Command\Download;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use PHPacker\PHPacker\Support\Config\ConfigManager;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->in('Feature')
    ->extend(Tests\TestCase::class)
    ->beforeEach(function () {
        $this->filesystem->remove(__DIR__ . '/_stubs/build');
    })
    ->afterEach(function () {
        $this->filesystem->remove(__DIR__ . '/_stubs/build');
    });

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/
// expect()->extend('toExitOk', fn () => $this->toBe(0));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// Bootstraps PHPacker
function app()
{
    return once(function () {
        $app = new Application('phpacker', 'test');
        $dispatcher = new EventDispatcher;

        ConfigManager::bootstrap($dispatcher);

        $app->add(new Build);
        $app->add(new Download);

        $app->setDispatcher($dispatcher);

        return $app;
    });
}

// This is used to call the PHPacker commands for real.
// Used for end to end testing of the executables.
function command(string $name, array $arguments = [])
{
    $command = new CommandTester(app()->find($name));

    $command->execute($arguments);

    return test()->expect($command);
}

// This is used to run a bootstrapped command so we can test the
// config merge priority using the event pattern, mimicking how
// the functionality is integrated with the app's commands.
function commandDouble(array $input = [])
{

    $stub = once(function () {
        $stub = new CommandDouble;

        app()->add($stub);

        return $stub;
    });

    $input = new ArrayInput([
        'command' => 'stub',
        ...$input,
    ]);

    $input->setInteractive(false);

    $output = new BufferedOutput;

    return test()->expect((object) [
        'exit_code' => app()->doRun($input, $output),
        'output' => $output->fetch(),
    ]);
}

// Helper to assert on shell commands.
// Used to execute the build executable.
function shell(string $command)
{
    $process = Process::fromShellCommandline($command);

    $process->run();

    return test()->expect($process);
}

function getPlatform()
{
    if (PHP_OS === 'WINNT' || PHP_OS === 'WIN32' || PHP_OS === 'Windows') {
        return 'windows';
    } elseif (PHP_OS === 'Darwin') {
        return 'mac';
    } elseif (PHP_OS === 'Linux') {
        return 'linux';
    }
}

function getArch()
{
    $machine = php_uname('m');

    return (strpos($machine, 'arm') !== false || strpos($machine, 'aarch') !== false) ? 'arm' : 'x64';
}
