<?php

declare(strict_types=1);

namespace App;

class Result
{
    public function __construct(
        private readonly string $representationTxt,
        private readonly string $representationJson,
        private readonly string $mappingJson,
    ) {
    }

    public function getRepresentationTxt(): string
    {
        return $this->representationTxt;
    }

    public function getRepresentationJson(): string
    {
        return $this->representationJson;
    }

    public function getMappingJson(): string
    {
        return $this->mappingJson;
    }
}
