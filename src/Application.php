<?php

declare(strict_types=1);

namespace App;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;

use function assert;
use function file_put_contents;
use function is_bool;
use function is_dir;
use function is_string;
use function is_writable;

class Application extends SingleCommandApplication
{
    public function __construct()
    {
        parent::__construct('Exercism PHP Representer');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setVersion('1.0.0');
        $this->addArgument('exercise-slug', InputArgument::REQUIRED, 'Slug of the exercise');
        $this->addArgument('solution-dir', InputArgument::REQUIRED, 'Directory of the solution');
        $this->addArgument('output-dir', InputArgument::REQUIRED, 'Writable directory for the representation');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run (do not write files)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exerciseSlug = $input->getArgument('exercise-slug');
        $solutionDir  = $input->getArgument('solution-dir');
        $outputDir    = $input->getArgument('output-dir');
        $dryRun       = $input->getOption('dry-run');

        if (! is_string($exerciseSlug)) {
            throw new RuntimeException('exercise-slug must be a string'); // @codeCoverageIgnore
        }

        if (! is_string($solutionDir) || ! is_dir($solutionDir)) {
            throw new RuntimeException('solution-dir must be a directory'); // @codeCoverageIgnore
        }

        if (! is_string($outputDir) || ! is_dir($outputDir) || ! is_writable($outputDir)) {
            throw new RuntimeException('output-dir must be a writable directory'); // @codeCoverageIgnore
        }

        assert(is_bool($dryRun));

        $logger = new ConsoleLogger($output);

        $logger->info('Exercise slug: ' . $exerciseSlug);
        $logger->info('Solution directory: ' . $solutionDir);
        $logger->info('Output directory: ' . $outputDir);

        $representer = new DirectoryRepresenter($solutionDir, $logger);
        $result      = $representer->represent();

        if ($dryRun) {
            $logger->info('Dry run: not writing files');
        } else {
            file_put_contents($outputDir . '/representation.txt', $result->getRepresentationTxt());
            file_put_contents($outputDir . '/representation.json', $result->getRepresentationJson());
            file_put_contents($outputDir . '/mapping.json', $result->getMappingJson());
        }

        return self::SUCCESS;
    }
}
