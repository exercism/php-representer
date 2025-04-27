<?php

declare(strict_types=1);

namespace App\Tests;

class ClassInstantiationTest extends RepresenterTestCase
{
    /**
     * Ensures that we represent with parentheses to avoid having to re-run the representer.
     */
    public function testBackwardCompatibility(): void
    {
        $this->assertRepresentation(
            <<<'CODE'
            <?php
            new MyClass()->method();
            CODE,
            <<<'CODE'
            (new C0())->m0();
            CODE,
            '{"C0":"MyClass","m0":"method"}',
        );
    }

    public function testClassInstantiation(): void
    {
        $this->assertSameRepresentation(
            <<<'CODE'
            <?php
            (new MyClass())->method();
            CODE,
            <<<'CODE'
            <?php
            new MyClass()->method();
            CODE,
        );
    }
}
