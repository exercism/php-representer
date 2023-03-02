<?php

declare(strict_types=1);

namespace App\Tests;

class StripCommentTest extends RepresenterTest
{
    public function testStripComment(): void
    {
        $code = <<<'CODE'
            <?php
            
            /**
             * This is a comment
             */
            function helloWorld()
            {
                // This is a comment
                return "Hello World!"; // This is a comment
            }
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
        function fn0()
        {
            return "Hello World!";
        }
        EOF,
            '{"fn0":"helloworld"}',
        );
    }
}
