<?php

declare(strict_types=1);

namespace App\Tests;

class CastTest extends RepresenterTestCase
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

    public function testBooleanCast(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = (boolean) true;
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = (bool) true;
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }

    public function testIntegerCast(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = (integer) true;
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = (int) true;
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }

    public function testBinaryCast(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = (binary) true;
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = (string) true;
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }
}
