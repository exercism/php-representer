<?php

declare(strict_types=1);

namespace App\Tests;

class ArraySyntaxTest extends RepresenterTestCase
{
    public function testArraySyntax(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = [0, 1, 'a' => 'b', 'c' => 'd'];
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = array(0, 1, 'a' => 'b', 'c' => 'd');
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }

    public function testMultiLine(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            $a = [
                0,
                1,
                'a' => 'b',
                'c' => 'd',
            ];
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            $a = [0, 1, 'a' => 'b', 'c' => 'd'];
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }
}
