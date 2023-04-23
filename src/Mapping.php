<?php

declare(strict_types=1);

namespace App;

use function array_flip;
use function array_key_exists;
use function count;
use function get_defined_functions;
use function json_encode;
use function ksort;
use function mb_strtolower;

use const JSON_FORCE_OBJECT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

class Mapping
{
    private const FUNCTION_PREFIX = 'fn';
    private const VARIABLE_PREFIX = 'v';
    private const CLASS_PREFIX = 'C';
    private const METHOD_PREFIX = 'm';

    /** @var array<string, MappingEntry> */
    private array $invertedFunctionMapping = [];
    /** @var array<string, MappingEntry> */
    private array $invertedVariableMapping = [];
    /** @var array<string, MappingEntry> */
    private array $invertedClassMapping = [];
    /** @var array<string, MappingEntry> */
    private array $invertedMethodMapping = [];

    /** @var array<string, int> Array to search rapidly for internal functions */
    private readonly array $internalFunctions;

    public function __construct()
    {
        $this->internalFunctions = array_flip(get_defined_functions()['internal']);
    }

    public function toJson(): string
    {
        $mapping = [];
        foreach ($this->invertedClassMapping as $entry) {
            $mapping[$entry->getStableName()] = $entry->getMostCommonName();
        }

        foreach ($this->invertedFunctionMapping as $entry) {
            $mapping[$entry->getStableName()] = $entry->getMostCommonName();
        }

        foreach ($this->invertedMethodMapping as $entry) {
            $mapping[$entry->getStableName()] = $entry->getMostCommonName();
        }

        foreach ($this->invertedVariableMapping as $entry) {
            $mapping[$entry->getStableName()] = $entry->getMostCommonName();
        }

        ksort($mapping);

        return json_encode($mapping, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * TRANSFORM: normalize PHP's aliased function names (only Base syntax)
     *
     * @see https://www.php.net/manual/en/aliases.php
     */
    private function functionAlias(string $name): string
    {
        return match ($name) {
            'chop' => 'rtrim',
            // 'close' => 'closedir', // close() does not exist in PHP 8.0 (no documentation)
            'doubleval' => 'floatval',
            'fputs' => 'fwrite',
            'ini_alter' => 'ini_set',
            'is_double' => 'is_float',
            'is_integer' => 'is_int',
            'is_long' => 'is_int',
            // 'is_real' => 'is_float', // is_real() is removed in PHP 8.0
            'is_writeable' => 'is_writable',
            'join' => 'implode',
            'key_exists' => 'array_key_exists',
            'pos' => 'current',
            'show_source' => 'highlight_file',
            'sizeof' => 'count',
            'strchr' => 'strstr',
            default => $name,
        };
    }

    public function addFunction(string $name): string
    {
        // TRANSFORM: Function names are case-insensitive in PHP
        $lcName = mb_strtolower($name);
        $entry = $this->invertedFunctionMapping[$lcName] ?? null;
        if ($entry === null) {
            // Do not rename built-in functions except aliased ones, functions_exists() includes user-defined functions
            if (array_key_exists($lcName, $this->internalFunctions)) {
                $unaliasedName = $this->functionAlias($lcName);
                if ($unaliasedName === $lcName) {
                    return $unaliasedName;
                }

                $stableName = $unaliasedName;
            } else {
                $stableName = self::FUNCTION_PREFIX . count($this->invertedFunctionMapping);
            }

            $this->invertedFunctionMapping[$lcName] = $entry = new MappingEntry($stableName);
        }

        $entry->addValue($name);

        return $entry->getStableName();
    }

    public function addVariable(string $name): string
    {
        $entry = $this->invertedVariableMapping[$name] ?? null;
        if ($entry === null) {
            $stableName = self::VARIABLE_PREFIX . count($this->invertedVariableMapping);
            $this->invertedVariableMapping[$name] = $entry = new MappingEntry($stableName);
            $entry->addValue($name);
        }

        return $entry->getStableName();
    }

    public function addClass(string $name): string
    {
        // TRANSFORM: Class names are case-insensitive in PHP
        $lcName = mb_strtolower($name);
        $entry = $this->invertedClassMapping[$lcName]
            ??= new MappingEntry(self::CLASS_PREFIX . count($this->invertedClassMapping));
        $entry->addValue($name);

        return $entry->getStableName();
    }

    public function addMethod(string $name): string
    {
        // TRANSFORM: Method names are case-insensitive in PHP
        $lcName = mb_strtolower($name);
        $entry = $this->invertedMethodMapping[$lcName]
            ??= new MappingEntry(self::METHOD_PREFIX . count($this->invertedMethodMapping));
        $entry->addValue($name);

        return $entry->getStableName();
    }
}
