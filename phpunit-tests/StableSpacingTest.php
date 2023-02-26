<?php

declare(strict_types=1);

namespace App\Tests;

class StableSpacingTest extends RepresenterTest
{
    public function testStableSpacing(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            function helloWorld()
            {
            
                return    "Hello World!"   ;
            }
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            function helloWorld()
            {
                return "Hello World!";
            }
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }
}
