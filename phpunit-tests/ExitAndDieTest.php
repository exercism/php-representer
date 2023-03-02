<?php

declare(strict_types=1);

namespace App\Tests;

class ExitAndDieTest extends RepresenterTest
{
    public function testExitAndDie(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            exit(1);
            exit();
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            die(1);
            die();
            CODE;

        $this->assertSameRepresentation($codeA, $codeB);
    }
}
