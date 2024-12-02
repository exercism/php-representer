<?php

declare(strict_types=1);

namespace App\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function var_export;

/**
 * @see https://www.php.net/manual/en/language.types.string.php
 *
 * We will convert all strings to singlequotes as reported by `var_export()`
 */
class StringTest extends RepresenterTestCase
{
    public function testDoubleQuotes(): void
    {
        $code = <<<'CODE'
            <?php
            "test";
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'test';
            EOF,
            '{}',
        );
    }

    public function testDoubleQuotesWithEscapes(): void
    {
        // We use `\\\\(\n)` because `\\(\n)` will be simplified to `\(\n)` by PhpParser
        $code = <<<'CODE'
            <?php
            "\n\r\n\t\n\v\n\e\n\f\n\\\\\n\$\n\"\n\377\n\x26\n\u{2665}";
            CODE;

        $this->assertRepresentation(
            $code,
            var_export("\n\r\n\t\n\v\n\e\n\f\n\\\\\n\$\n\"\n\377\n\x26\n\u{2665}", true) . ';',
            '{}',
        );
    }

    public function testDoubleQuotesEncapsulation(): void
    {
        // `${a}` is deprecated since PHP8.2
        $code = <<<'CODE'
            <?php
            "encapsed $a or {$a} or ${a}.";
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'encapsed ' . $v0 . ' or ' . $v0 . ' or ' . $v0 . '.';
            EOF,
            '{"v0":"a"}',
        );
    }

    public function testDoubleQuotesSimpleEncapsulation(): void
    {
        $code = <<<'CODE'
            <?php
            "$a";
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            $a;
            EOF,
            '{}',
        );
    }

    public function testHeredoc(): void
    {
        $code = <<<'CODE'
            <?php
            <<<EOT
            test
            EOT;
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'test';
            EOF,
            '{}',
        );
    }

    public function testHeredocWithEscapes(): void
    {
        $code = <<<'CODE'
            <?php
            <<<EOT
            Test "$a"\x41.
            EOT;
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'Test "' . $v0 . '"A.';
            EOF,
            '{"v0":"a"}',
        );
    }

    public function testNowdoc(): void
    {
        $code = <<<'CODE'
            <?php
            <<<'EOD'
            test
            EOD;
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'test';
            EOF,
            '{}',
        );
    }

    /** @return Generator<string, array{string, string}, void, void> */
    public static function uselessConcatenationProvider(): iterable
    {
        yield 'basic' => ["'testA' . 'testB' . 'testC';", "'testAtestBtestC';"];
        yield 'right' => ["'testA' . ('testB' . 'testC');", "'testAtestBtestC';"];
        yield 'left' => ["('testA' . 'testB') . 'testC';", "'testAtestBtestC';"];
        yield 'both' => ["('testA' . 'testB') . ('testC' . 'testD');", "'testAtestBtestCtestD';"];
    }

    #[DataProvider('uselessConcatenationProvider')]
    public function testUselessConcatenation(string $input, string $output): void
    {
        $code = <<<CODE
            <?php
            $input
            CODE;

        $this->assertRepresentation(
            $code,
            <<<EOF
            $output
            EOF,
            '{}',
        );
    }

    /** @return Generator<string, array{string, string}, void, void> */
    public static function uselessInterpolatedConcatenationProvider(): iterable
    {
        yield 'left left' => ['"{$c}testA" . \'testB\';', '$v0 . \'testAtestB\';'];
        yield 'left right' => ['"testA{$c}" . \'testB\';', '\'testA\' . $v0 . \'testB\';'];
        yield 'right left' => ['\'testA\' . "{$c}testB";', '\'testA\' . $v0 . \'testB\';'];
        yield 'right right' => ['\'testA\' . "testB{$c}";', '\'testAtestB\' . $v0;'];
        yield 'left left and right right' => ['"{$c}testA" . "testB{$c}";', '$v0 . \'testAtestB\' . $v0;'];
    }

    #[DataProvider('uselessInterpolatedConcatenationProvider')]
    public function testUselessInterpolatedConcatenation(string $input, string $output): void
    {
        $code = <<<CODE
            <?php
            $input
            CODE;

        $this->assertRepresentation(
            $code,
            <<<EOF
            $output
            EOF,
            '{"v0":"c"}',
        );
    }
}
