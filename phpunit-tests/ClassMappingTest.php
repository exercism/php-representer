<?php

declare(strict_types=1);

namespace App\Tests;

class ClassMappingTest extends RepresenterTestCase
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
        CODE, '{"C0":"helloworld","v0":"a"}');
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

    public function testCaseInsensitiveClass(): void
    {
        // ΤΑΞΗ (uppercase) and ταξη (lowercase) means "class" in Greek
        $code = <<<'CODE'
            <?php
            class ΤΑΞΗ {}
            new ταξη();
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        class C0
        {
        }
        new C0();
        CODE, '{"C0":"ταξη"}');
    }
}
