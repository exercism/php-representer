<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function count;
use function escapeshellarg;
use function mkdir;
use function scandir;
use function shell_exec;

class ConsoleTest extends RepresenterTest
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $application = new Application();
        $application->setAutoExit(false); // Required else the command will exit the test
        $this->commandTester = new CommandTester($application);
    }

    public function testConsole(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/normal-input',
            'output-dir' => __DIR__ . '/data/output',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exercise slug: hello-world', $display);
        $this->assertStringContainsString('[debug] AST Before normalization', $display);
        $this->assertStringContainsString('[debug] AST After normalization', $display);
    }

    public function testConsoleMissingArgument(): void
    {
        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString(
            'Not enough arguments (missing: "exercise-slug, solution-dir, output-dir")',
            $display,
        );
    }

    public function testEmpty(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/empty-input',
            'output-dir' => __DIR__ . '/data/output',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.meta/config.json: Unable to read file', $display);
    }

    public function testEmptyFiles(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/empty-files-input',
            'output-dir' => __DIR__ . '/data/output',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.meta/config.json: `files.solution` key is empty', $display);
    }

    public function testInvalidFiles(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/invalid-files-input',
            'output-dir' => __DIR__ . '/data/output',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.meta/config.json: missing or invalid `files.solution` key', $display);
    }

    public function testNonExistingFiles(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/nonexisting-files-input',
            'output-dir' => __DIR__ . '/data/output',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('solution.php: Unable to read file', $display);
    }

    public function testInvalid(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/invalid-php-code-input',
            'output-dir' => __DIR__ . '/data/output',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Unable to parse code:', $display);
    }

    public function testDryRun(): void
    {
        $emptyDirectory = __DIR__ . '/data/output/empty';
        shell_exec('rm -rf ' . escapeshellarg($emptyDirectory));
        mkdir($emptyDirectory, 0777, true);

        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/normal-input',
            'output-dir' => $emptyDirectory,
            '--dry-run' => true,
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('[info] Dry run: not writing files', $display);
        $this->assertEquals(
            2,
            count(scandir($emptyDirectory)),
            'Directory `' . $emptyDirectory . '` should be empty after running with `--dry-run`.',
        );
    }
}
