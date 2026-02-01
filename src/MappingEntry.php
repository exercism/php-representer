<?php

declare(strict_types=1);

namespace App;

use function array_count_values;
use function array_key_first;
use function arsort;

class MappingEntry
{
    /** @var array<string> */
    private array $names = [];

    public function __construct(private string $stableName)
    {
    }

    public function addValue(string $value): void
    {
        $this->names[] = $value;
    }

    public function getStableName(): string
    {
        return $this->stableName;
    }

    public function getMostCommonName(): string
    {
        $values = array_count_values($this->names);
        arsort($values);

        return array_key_first($values);
    }
}
