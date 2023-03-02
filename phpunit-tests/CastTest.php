<?php

declare(strict_types=1);

namespace App\Tests;

class CastTest extends RepresenterTest
{
    public function testFloatCast(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = (double) 1.0;
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = (float) 1.0;
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }

    public function testRealCast(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = (double) 1.0;
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = (real) 1.0;
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }
}
