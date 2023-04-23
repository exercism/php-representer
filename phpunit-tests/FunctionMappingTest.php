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

        $this->assertSameRepresentationWithMapping($codeA, $codeB, '{"fn0":"helloWorldA"}', '{"fn0":"helloWorldB"}');
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
            '{"fn0":"ΛΕΙΤΟΥΡΓΙΑ"}',
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

    public function testReplacesFunctionAliases(): void
    {
        $code = <<<'CODE'
            <?php
            chop();
            doubleval();
            fputs();
            ini_alter();
            is_double();
            is_integer();
            is_long();
            is_writeable();
            join();
            key_exists();
            pos();
            show_source();
            sizeof();
            strchr();
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'CODE'
            rtrim();
            floatval();
            fwrite();
            ini_set();
            is_float();
            is_int();
            is_int();
            is_writable();
            implode();
            array_key_exists();
            current();
            highlight_file();
            count();
            strstr();
            CODE,
            // phpcs:ignore Generic.Files.LineLength -- Not worth splitting into multiple lines
            '{"array_key_exists":"key_exists","count":"sizeof","current":"pos","floatval":"doubleval","fwrite":"fputs","highlight_file":"show_source","implode":"join","ini_set":"ini_alter","is_float":"is_double","is_int":"is_long","is_writable":"is_writeable","rtrim":"chop","strstr":"strchr"}',
        );
    }

    public function testCaseInsensitiveMultipleOccurrences(): void
    {
        $code = <<<'CODE'
            <?php
            
            function A() {}
            a();
            a();
            CODE;

        $this->assertRepresentation($code, <<<'CODE'
        function fn0()
        {
        }
        fn0();
        fn0();
        CODE
            , '{"fn0":"a"}');
    }
}
