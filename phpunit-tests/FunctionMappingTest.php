<?php

declare(strict_types=1);

namespace App\Tests;

class FunctionMappingTest extends RepresenterTestCase
{
    public function testFunctionMappingCoherence(): void
    {
        $code = <<<'CODE'
            <?php
            
            function a() { return 'test'; }
            function b() { return a(); }
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        function fn0()
        {
            return 'test';
        }
        function fn1()
        {
            return fn0();
        }
        CODE
            , '{"fn0":"a","fn1":"b"}');
    }

    public function testFunctionMapping(): void
    {
        $codeA = <<<'CODE'
            <?php
            
            function helloWorldA()
            {
                return "Hello World!";
            }
            CODE;

        $codeB = <<<'CODE'
            <?php
            
            function helloWorldB()
            {
                return "Hello World!";
            }
            CODE;

        $this->assertSameRepresentationWithMapping($codeA, $codeB, '{"fn0":"helloworlda"}', '{"fn0":"helloworldb"}');
    }

    public function testDoesNotRenameCoreFunctions(): void
    {
        $code = <<<'CODE'
            <?php
            echo implode(' ', array_map('strtolower', ['Hello', 'World']));
            CODE;

        $this->assertRepresentation(
            $code,
            'echo implode(\' \', array_map(\'strtolower\', [\'Hello\', \'World\']));',
            '{}',
        );
    }

    public function testCaseInsensitiveFunctions(): void
    {
        // ΛΕΙΤΟΥΡΓΙΑ (uppercase) and λειτουργια (lowercase) means "function" in Greek
        $code = <<<'CODE'
            <?php
            function ΛΕΙΤΟΥΡΓΙΑ() {}
            λειτουργια();
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'CODE'
            function fn0()
            {
            }
            fn0();
            CODE,
            '{"fn0":"λειτουργια"}',
        );
    }

    public function testCaseInsensitiveNativeFunctions(): void
    {
        $code = <<<'CODE'
            <?php
            FunCtioN_ExiStS();
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'CODE'
            function_exists();
            CODE,
            '{}',
        );
    }

    public function testDoNotReplaceNameAsExpression(): void
    {
        $code = <<<'CODE'
            <?php
            function a() {}
            $a = 'a';
            $a();
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'CODE'
            function fn0()
            {
            }
            $v0 = 'a';
            $v0();
            CODE,
            '{"fn0":"a","v0":"a"}',
        );
    }
}
