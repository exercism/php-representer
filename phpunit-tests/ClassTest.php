<?php

declare(strict_types=1);

namespace App\Tests;

class ClassTest extends RepresenterTestCase
{
    public function testClassConstantType(): void
    {
        $this->assertSameRepresentation(
            <<<'CODE'
            <?php
            interface I {
                const string PHP = 'PHP 8.3';
            }
            
            class Php84 implements I {
                const string PHP = 'PHP 8.4';
            }
            CODE,
            <<<'CODE'
            <?php
            interface I {
                const PHP = 'PHP 8.3';
            }
            
            class Php84 implements I {
                const PHP = 'PHP 8.4';
            }
            CODE,
        );
    }
}
