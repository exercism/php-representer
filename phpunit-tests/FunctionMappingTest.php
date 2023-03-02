<?php

declare(strict_types=1);

namespace App\Tests;

class FunctionMappingTest extends RepresenterTest
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
        $code = <<<'CODE'
            <?php
            function A() {}
            a();
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'CODE'
            function fn0()
            {
            }
            fn0();
            CODE,
            '{"fn0":"a"}',
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
}
