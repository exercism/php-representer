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

use const JSON_FORCE_OBJECT;
use const JSON_THROW_ON_ERROR;

class Mapping
{
    private const FUNCTION_PREFIX = 'fn';
    private const VARIABLE_PREFIX = 'v';
    private const CLASS_PREFIX    = 'C';

    /** @var array<string, string> */
    private array $invertedFunctionMapping = [];
    /** @var array<string, string> */
    private array $invertedVariableMapping = [];
    /** @var array<string, string> */
    private array $invertedClassMapping = [];

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
        );

        ksort($mapping);

        return json_encode($mapping, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    public function addFunction(string $name): string
    {
        // Do not rename built-in functions, functions_exists() includes user-defined functions
        if (array_key_exists($name, $this->internalFunctions)) {
            return $name;
        }

        if (! isset($this->invertedFunctionMapping[$name])) {
            $stableName                           = self::FUNCTION_PREFIX . count($this->invertedFunctionMapping);
            $this->invertedFunctionMapping[$name] = $stableName;
        }

        return $this->invertedFunctionMapping[$name];
    }

    public function addVariable(string $name): string
    {
        if (! isset($this->invertedVariableMapping[$name])) {
            $stableName                           = self::VARIABLE_PREFIX . count($this->invertedVariableMapping);
            $this->invertedVariableMapping[$name] = $stableName;
        }

        return $this->invertedVariableMapping[$name];
    }

    public function addClass(string $name): string
    {
        if (! isset($this->invertedClassMapping[$name])) {
            $stableName                        = self::CLASS_PREFIX . count($this->invertedClassMapping);
            $this->invertedClassMapping[$name] = $stableName;
        }

        return $this->invertedClassMapping[$name];
    }
}
