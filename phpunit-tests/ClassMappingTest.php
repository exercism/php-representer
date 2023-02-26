<?php

declare(strict_types=1);

namespace App\Tests;

class ClassMappingTest extends RepresenterTest
{
    public function testClassMapping(): void
    {
        $code = <<<'CODE'
            <?php
            
            class HelloWorld {}
            $a = new HelloWorld();
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        class C0
        {
        }
        $v0 = new C0();
        CODE, '{"C0":"HelloWorld","v0":"a"}');
    }

    public function testAnonymousClassMapping(): void
    {
        $code = <<<'CODE'
            <?php
            
            $a = new class {};
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        $v0 = new class
        {
        };
        CODE, '{"v0":"a"}');
    }
}
