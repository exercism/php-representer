<?php

declare(strict_types=1);

namespace App;

use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function assert;
use function implode;
use function is_array;
use function is_string;
use function json_decode;

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
        private readonly Filesystem $solutionDir,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function represent(): Result
    {
        $configJson = $this->solutionDir->read('/.meta/config.json');
        $solutions = $this->parseSolutions($configJson);

        $mapping = new Mapping();
        $representer = new FilesRepresenter($mapping, $this->logger);
        $filesRepresentation = '';

        if (empty($solutions)) {
            $this->logger->warning('.meta/config.json: `files.solution` key is empty');
        }

        foreach ($solutions as $solution) {
            $this->logger->info('Representing solution file: ' . $solution);

            $code = $this->solutionDir->read($solution);

            $filesRepresentation .= '// file: ' . $solution . PHP_EOL;
            $filesRepresentation .= $representer->represent($code) . PHP_EOL;
        }

        return new Result(
            $filesRepresentation,
            '{"version": 2}',
            $mapping->toJson(),
        );
    }

    /** @return string[] */
    private function parseSolutions(string $configJson): array
    {
        $config = json_decode($configJson, true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($config), 'json_decode(..., true) should return an array');
        if (
            ! isset($config['files'])
            || ! is_array($config['files'])
            || ! isset($config['files']['solution'])
            || ! is_array($config['files']['solution'])
            || ! $this->isArrayOfString($config['files']['solution'])
        ) {
            throw new RuntimeException('.meta/config.json: missing or invalid `files.solution` key');
        }

        $solutions = $config['files']['solution'];

        $this->logger->info('.meta/config.json: Solutions files: ' . implode(', ', $solutions));

        return $solutions;
    }

    /**
     * @param mixed[] $array
     *
     * @phpstan-assert-if-true string[] $array
     */
    private function isArrayOfString(array $array): bool
    {
        foreach ($array as $element) {
            if (! is_string($element)) {
                return false;
            }
        }

        return true;
    }
}
