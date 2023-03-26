<?php

declare(strict_types=1);

namespace App;

use function array_flip;
use function array_key_exists;
use function array_merge;
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

    /** @var array<string, string> */
    private array $invertedFunctionMapping = [];
    /** @var array<string, string> */
    private array $invertedVariableMapping = [];
    /** @var array<string, string> */
    private array $invertedClassMapping = [];
    /** @var array<string, string> */
    private array $invertedMethodMapping = [];

    /** @var array<string, int> Array to search rapidly for internal functions */
    private readonly array $internalFunctions;

    public function __construct()
    {
        $this->internalFunctions = array_flip(get_defined_functions()['internal']);
    }

    public function toJson(): string
    {
        $mapping = array_merge(
            array_flip($this->invertedFunctionMapping),
            array_flip($this->invertedVariableMapping),
            array_flip($this->invertedClassMapping),
            array_flip($this->invertedMethodMapping),
        );

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
        $name = mb_strtolower($name);
        // Do not rename built-in functions except aliased ones, functions_exists() includes user-defined functions
        if (array_key_exists($name, $this->internalFunctions)) {
            $unaliasedName = $this->functionAlias($name);
            if ($unaliasedName !== $name) {
                $this->invertedFunctionMapping[$name] = $unaliasedName;
            }

            return $unaliasedName;
        }

        if (! isset($this->invertedFunctionMapping[$name])) {
            $stableName = self::FUNCTION_PREFIX . count($this->invertedFunctionMapping);
            $this->invertedFunctionMapping[$name] = $stableName;
        }

        return $this->invertedFunctionMapping[$name];
    }

    public function addVariable(string $name): string
    {
        if (! isset($this->invertedVariableMapping[$name])) {
            $stableName = self::VARIABLE_PREFIX . count($this->invertedVariableMapping);
            $this->invertedVariableMapping[$name] = $stableName;
        }

        return $this->invertedVariableMapping[$name];
    }

    public function addClass(string $name): string
    {
        // TRANSFORM: Class names are case-insensitive in PHP
        $name = mb_strtolower($name);
        if (! isset($this->invertedClassMapping[$name])) {
            $stableName = self::CLASS_PREFIX . count($this->invertedClassMapping);
            $this->invertedClassMapping[$name] = $stableName;
        }

        return $this->invertedClassMapping[$name];
    }

    public function addMethod(string $name): string
    {
        // TRANSFORM: Method names are case-insensitive in PHP
        $name = mb_strtolower($name);
        if (! isset($this->invertedMethodMapping[$name])) {
            $stableName = self::METHOD_PREFIX . count($this->invertedMethodMapping);
            $this->invertedMethodMapping[$name] = $stableName;
        }

        return $this->invertedMethodMapping[$name];
    }
}
