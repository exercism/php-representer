<?php

declare(strict_types=1);

namespace App\Tests;

use App\FilesRepresenter;
use App\Mapping;
use PHPUnit\Framework\TestCase;

abstract class RepresenterTestCase extends TestCase
{
    protected function assertRepresentation(string $code, string $expectedRepresentation, string $expectedMapping): void
    {
        $mapping = new Mapping();
        $representer = new FilesRepresenter($mapping);

        $result = $representer->represent($code);
        $this->assertEquals($expectedRepresentation, $result);
        $this->assertEquals($expectedMapping, $mapping->toJson());
    }

    protected function assertSameRepresentation(string $codeA, string $codeB): void
    {
        $resultA = (new FilesRepresenter())->represent($codeA);
        $resultB = (new FilesRepresenter())->represent($codeB);
        $this->assertEquals($resultA, $resultB);
    }

    protected function assertSameRepresentationWithMapping(
        string $codeA,
        string $codeB,
        string $mappingAJson,
        string $mappingBJson,
    ): void {
        $mappingA = new Mapping();
        $representerA = new FilesRepresenter($mappingA);

        $mappingB = new Mapping();
        $representerB = new FilesRepresenter($mappingB);

        $resultA = $representerA->represent($codeA);
        $resultB = $representerB->represent($codeB);
        $this->assertEquals($resultA, $resultB);
        $this->assertEquals($mappingAJson, $mappingA->toJson());
        $this->assertEquals($mappingBJson, $mappingB->toJson());
    }
}
