<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleTest extends RepresenterTestCase
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
        $this->assertStringContainsString('[info] Exercise slug: hello-world', $display);
        $this->assertMatchesRegularExpression(
            '#\[info] Solution directory: /.*/phpunit-tests/data/normal-input#',
            $display,
        );
        $this->assertMatchesRegularExpression('#\[info] Output directory: /.*/phpunit-tests/data/output#', $display);
        $this->assertStringContainsString('[info] .meta/config.json: Solutions files: solution.php', $display);
        $this->assertStringContainsString('[info] Representing solution file: solution.php', $display);
        $this->assertStringContainsString('[debug] AST Before normalization: array(', $display);
        $this->assertStringContainsString('[debug] AST After normalization: array(', $display);
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

    public function testMemoryOutput(): void
    {
        $input = [
            'exercise-slug' => 'hello-world',
            'solution-dir' => __DIR__ . '/data/normal-input',
            'output-dir' => 'memory://',
        ];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testVersion(): void
    {
        $input = ['--version' => ''];

        $this->commandTester->execute($input, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('1.0.0', $display);
    }
}
