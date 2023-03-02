<?php

declare(strict_types=1);

namespace App\Tests;

class VariableMappingTest extends RepresenterTest
{
    public function testVariableMapping(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            function helloWorld()
            {
                $a = "Hello World!";
                return $a;
            }
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            function helloWorld()
            {
                $b = "Hello World!";
                return $b;
            }
            CODE;

        $this->assertSameRepresentationWithMapping(
            $codeA,
            $codeB,
            '{"fn0":"helloworld","v0":"a"}',
            '{"fn0":"helloworld","v0":"b"}',
        );
    }
}
