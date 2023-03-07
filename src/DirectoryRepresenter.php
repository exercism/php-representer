<?php

declare(strict_types=1);

namespace App;

use Psr\Log\AbstractLogger;
use RuntimeException;

use function file_get_contents;
use function implode;
use function is_array;
use function json_decode;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

/**
 * This class represents a directory of solutions.
 *
 * Its main goal is to have a stable representation of the solutions presents in the given directory.
 */
class DirectoryRepresenter
{
    public function __construct(
        private readonly string $solutionDir,
        private readonly AbstractLogger $logger,
    ) {
    }

    public function represent(): Result
    {
        $configJson = @file_get_contents($this->solutionDir . '/.meta/config.json');
        if ($configJson === false) {
            throw new RuntimeException('.meta/config.json: Unable to read file');
        }

        $config = json_decode($configJson, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($config) || ! isset($config['files']['solution']) || ! is_array($config['files']['solution'])) {
            throw new RuntimeException('.meta/config.json: missing or invalid `files.solution` key');
        }

        $solutions = $config['files']['solution'];
        $this->logger->info('.meta/config.json: Solutions files: ' . implode(', ', $solutions));

        $mapping             = new Mapping();
        $representer         = new FilesRepresenter($mapping, $this->logger);
        $filesRepresentation = '';

        if (empty($solutions)) {
            $this->logger->warning('.meta/config.json: `files.solution` key is empty');
        }

        foreach ($solutions as $solution) {
            $this->logger->info('Representing solution file: ' . $solution);

            $solutionPath = $this->solutionDir . DIRECTORY_SEPARATOR . $solution;
            $code         = @file_get_contents($solutionPath);
            if ($code === false) {
                throw new RuntimeException($solutionPath . ': Unable to read file');
            }

            $filesRepresentation .= '// file: ' . $solution . PHP_EOL;
            $filesRepresentation .= $representer->represent($code) . PHP_EOL;
        }

        return new Result(
            $filesRepresentation,
            '{"version":1}',
            $mapping->toJson(),
        );
    }
}
