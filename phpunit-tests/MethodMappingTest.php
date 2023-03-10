<?php

declare(strict_types=1);

namespace App\Tests;

class MethodMappingTest extends RepresenterTestCase
{
    public function testMethodMapping(): void
    {
        $code = <<<'CODE'
            <?php
            
            class A {
                public function a() {}
            }
            
            A::a();
            $a->a();
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        class C0
        {
            public function m0()
            {
            }
        }
        C0::m0();
        $v0->m0();
        CODE, '{"C0":"a","m0":"a","v0":"a"}');
    }

    public function testCaseInsensitiveMethod(): void
    {
        // ΜΕΘΟΔΟΣ (uppercase) and μέθοδος (lowercase) means "method" in Greek
        $code = <<<'CODE'
            <?php
            class A {
                public static function ΜΕΘΟΔΟΣ() {}
            }
            
            A::μέθοδος();
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        class C0
        {
            public static function m0()
            {
            }
        }
        C0::m1();
        CODE, '{"C0":"a","m0":"μεθοδοσ","m1":"μέθοδος"}');
    }

    public function testPublicModifier(): void
    {
        $code = <<<'CODE'
            <?php
            class A {
                private function a() {}
                protected function b() {}
            }
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        class C0
        {
            public function m0()
            {
            }
            public function m1()
            {
            }
        }
        CODE, '{"C0":"a","m0":"a","m1":"b"}');
    }

    public function testMethodExpression(): void
    {
        $code = <<<'CODE'
            <?php
            ${a}::${a}();
            ${a}->${a}();
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        ${a}::${a}();
        ${a}->{${a}}();
        CODE, '{}');
    }
}
