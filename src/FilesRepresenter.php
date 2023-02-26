<?php

declare(strict_types=1);

namespace App;

use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\String\LazyString;
use Throwable;

use function assert;

use const PHP_EOL;

/**
 * Represent PHP code as a string using and hydrating the provided Mapping.
 */
class FilesRepresenter
{
    private Parser $parser;
    private Standard $prettyPrinter;

    public function __construct(
        private Mapping $mapping = new Mapping(),
        private AbstractLogger $logger = new NullLogger(),
    ) {
        $this->parser        = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->prettyPrinter = new Standard();
    }

    public function represent(string $code): string
    {
        try {
            $ast = $this->parser->parse($code);
        } catch (Throwable $e) {
            // Parsing error is not an exception, we should still be able to represent the code
            $this->logger->error(
                'Unable to parse code: {{code}}' . PHP_EOL . 'exception: {{exception}}',
                ['code' => $code, 'exception' => $e],
            );

            return $code;
        }

        assert($ast !== null, 'Ast should not be null. Check parser\'s `$errorHandler`.');

        $this->logger->debug(LazyString::fromCallable(
            static fn () => 'AST Before normalization: ' . (new NodeDumper())->dump($ast)
        ));

        $visitor   = new NodeVisitor($this->mapping);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $this->logger->debug(LazyString::fromCallable(
            static fn () => 'AST After normalization: ' . (new NodeDumper())->dump($ast)
        ));

        return $this->prettyPrinter->prettyPrint($ast);
    }
}
