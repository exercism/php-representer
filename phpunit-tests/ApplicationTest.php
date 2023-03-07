<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ApplicationTest extends RepresenterTestCase
{
    public function testNormal(): void
    {
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{"files":{"solution":["solution.php"]}}');
        $inputFs->write('solution.php', '<?php $a = 1');

        $output = new InMemoryFilesystemAdapter();
        $outputFs = new Filesystem($output);

        $application = new Application();
        $application->represent($inputFs, $outputFs, new NullLogger());

        $this->assertTrue($outputFs->fileExists('/representation.txt'));
        $this->assertTrue($outputFs->fileExists('/representation.json'));
        $this->assertTrue($outputFs->fileExists('/mapping.json'));
    }

    public function testConfigMultipleFilesSolution(): void
    {
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{"files":{"solution":["solution1.php","solution2.php"]}}');
        $inputFs->write('solution1.php', '<?php $a = 1');
        $inputFs->write('solution2.php', '<?php $a = 1');

        $output = new InMemoryFilesystemAdapter();
        $outputFs = new Filesystem($output);

        $application = new Application();
        $application->represent($inputFs, $outputFs, new NullLogger());

        $this->assertEquals(
            "// file: solution1.php\n<?php \$a = 1\n// file: solution2.php\n<?php \$a = 1\n",
            $outputFs->read('/representation.txt'),
        );
    }

    public function testEmptySolutionDirectory(): void
    {
        $this->expectException(UnableToReadFile::class);
        $input = new InMemoryFilesystemAdapter();
        $output = new InMemoryFilesystemAdapter();

        $application = new Application();
        $application->represent(new Filesystem($input), new Filesystem($output), new NullLogger());
    }

    public function testConfigEmptyFilesSolution(): void
    {
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{"files":{"solution":[]}}');
        $output = new InMemoryFilesystemAdapter();

        $consoleOutput = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($consoleOutput);

        $application = new Application();
        $application->represent($inputFs, new Filesystem($output), $logger);

        $display = $consoleOutput->fetch();
        $this->assertStringContainsString('.meta/config.json: `files.solution` key is empty', $display);
    }

    public function testConfigInvalidFilesSolutionValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('#^\.meta/config\.json: missing or invalid `files\.solution` key$#');
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{"files":{"solution":true}}');
        $output = new InMemoryFilesystemAdapter();

        $application = new Application();
        $application->represent($inputFs, new Filesystem($output), new NullLogger());
    }

    public function testConfigMissingFilesSolution(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('#^\.meta/config\.json: missing or invalid `files\.solution` key$#');
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{}');
        $output = new InMemoryFilesystemAdapter();

        $application = new Application();
        $application->represent($inputFs, new Filesystem($output), new NullLogger());
    }

    public function testNonExistingFiles(): void
    {
        $this->expectException(UnableToReadFile::class);
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{"files":{"solution":["solution.php"]}}');
        $output = new InMemoryFilesystemAdapter();

        $application = new Application();
        $application->represent($inputFs, new Filesystem($output), new NullLogger());
    }

    public function testInvalidPhpContent(): void
    {
        $input = new InMemoryFilesystemAdapter();
        $inputFs = new Filesystem($input);
        $inputFs->write('.meta/config.json', '{"files":{"solution":["solution.php"]}}');
        // This is an invalid php file (this is rust code)
        $inputFs->write('solution.php', '<?php fn main() { println!("Hello World!"); }');

        $output = new InMemoryFilesystemAdapter();

        $consoleOutput = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($consoleOutput);

        $application = new Application();
        $application->represent($inputFs, new Filesystem($output), $logger);

        $display = $consoleOutput->fetch();
        $this->assertStringContainsString('[error] Unable to parse code: <?php', $display);
        $this->assertStringContainsString('exception: PhpParser\Error', $display);
    }
}
