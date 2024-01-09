<?php

declare(strict_types=1);

namespace App\Tests;

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
        $code = <<<'CODE'
            <?php
            "encapsed $a or ${a}.";
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'encapsed ' . $v0 . ' or ' . $v0 . '.';
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

    public function testUselessConcatenation(): void
    {
        $code = <<<'CODE'
            <?php
            'testA' . 'testB' . 'testC';
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'testAtestBtestC';
            EOF,
            '{}',
        );
    }

    public function testUselessRightConcatenation(): void
    {
        $code = <<<'CODE'
            <?php
            'testA' . ('testB' . 'testC');
            CODE;

        $this->assertRepresentation(
            $code,
            <<<'EOF'
            'testAtestBtestC';
            EOF,
            '{}',
        );
    }
}
