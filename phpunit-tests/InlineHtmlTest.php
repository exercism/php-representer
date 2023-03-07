<?php

declare(strict_types=1);

namespace App\Tests;

class InlineHtmlTest extends RepresenterTestCase
{
    public function testInlineHtml(): void
    {
        $codeA = <<<'CODE'
            
            This is inline HTML.
            
            
            <?php
            
            $a = 1;
            
            ?>
            
            
            <php
            
            
            ?>

            Again inline HTML.
            CODE;

        $codeB = <<<'CODE'
            <?php
            $a = 1;
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }
}
