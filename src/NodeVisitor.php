<?php

declare(strict_types=1);

namespace App;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use function array_map;
use function array_shift;
use function assert;
use function is_string;

/**
 * Apply transformations to normalize the AST
 */
class NodeVisitor extends NodeVisitorAbstract
{
    public function __construct(private Mapping $mapping)
    {
    }

    /**
     * TRANSFORM: Replace function name with stable name
     */
    private function replaceFunctionName(Function_ $function): void
    {
        $function->name = new Identifier($this->mapping->addFunction($function->name->toString()));
    }

    /**
     * TRANSFORM: Replace function name with stable name
     */
    private function replaceFunctionCallName(FuncCall $function): void
    {
        $name = $function->name;
        if ($name instanceof Name) {
            $function->name = new Name($this->mapping->addFunction($name->toString()));
        }
    }

    /**
     * TRANSFORM: Replace variable name with stable name
     */
    private function replaceVariableName(Variable $variable): void
    {
        $name = $variable->name;
        if (is_string($name)) {
            $variable->name = $this->mapping->addVariable($name);
        }
    }

    /**
     * TRANSFORM: Replace class name with stable name
     */
    private function replaceClassName(Class_ $class): void
    {
        if ($class->name === null) {
            return;
        }

        $class->name = new Identifier($this->mapping->addClass($class->name->toString()));
    }

    /**
     * TRANSFORM: Replace class name with stable name
     */
    private function replaceNewClassName(New_ $class): void
    {
        $className = $class->class;
        if ($className instanceof Name) {
            $class->class = new Name($this->mapping->addClass($className->toString()));
        }
    }

    /**
     * TRANSFORM: All strings are single quotes
     */
    private function normalizeString(String_ $string): void
    {
        $string->setAttribute('kind', String_::KIND_SINGLE_QUOTED);
    }

    /**
     * TRANSFORM: All encapsed strings are single quotes concatenation
     */
    private function normalizeEncapsedString(Encapsed $string): Node
    {
        $parts = array_map(
            static fn (Node $part) => $part instanceof EncapsedStringPart
                ? new String_($part->value, ['kind' => String_::KIND_SINGLE_QUOTED])
                : $part,
            $string->parts,
        );

        $left = array_shift($parts);
        assert($left !== null, 'Encapsed string had 0 part.');
        while ($right = array_shift($parts)) {
            $left = new Concat($left, $right);
        }

        return $left;
    }

    /**
     * TRANSFORM: Simplify useless concat such as `'a' . 'b'` => `'ab'`
     */
    private function simplifyUselessConcat(Concat $concat): String_|null
    {
        if ($concat->left instanceof String_ && $concat->right instanceof String_) {
            return new String_(
                $concat->left->value . $concat->right->value,
                ['kind' => String_::KIND_SINGLE_QUOTED],
            );
        }

        return null;
    }

    /**
     * TRANSFORM: Normalize array
     */
    private function normalizeArray(Array_ $node): void
    {
        $node->setAttribute('kind', Array_::KIND_SHORT);
    }

    /**
     * TRANSFORM: `die` is an alias for `exit`
     */
    private function normalizeExit(Exit_ $node): void
    {
        $node->setAttribute('kind', Exit_::KIND_EXIT);
    }

    /**
     * TRANSFORM: `(double)`, `(float)` and `(real)` are all aliases for `(double)`
     */
    private function normalizeCastDouble(Double $node): void
    {
        $node->setAttribute('kind', Double::KIND_DOUBLE);
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        // TRANSFORM: Strip comments from representation
        $node->setAttribute('comments', []);

        if ($node instanceof Function_) {
            $this->replaceFunctionName($node);
        } elseif ($node instanceof FuncCall) {
            $this->replaceFunctionCallName($node);
        } elseif ($node instanceof Variable) {
            $this->replaceVariableName($node);
        } elseif ($node instanceof Class_) {
            $this->replaceClassName($node);
        } elseif ($node instanceof New_) {
            $this->replaceNewClassName($node);
        } elseif ($node instanceof String_) {
            $this->normalizeString($node);
        } elseif ($node instanceof Encapsed) {
            return $this->normalizeEncapsedString($node);
        } elseif ($node instanceof Array_) {
            $this->normalizeArray($node);
        } elseif ($node instanceof Exit_) {
            $this->normalizeExit($node);
        } elseif ($node instanceof Double) {
            $this->normalizeCastDouble($node);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Concat) {
            return $this->simplifyUselessConcat($node);
        }

        // TRANSFORM: Remove empty statements from representation
        if ($node instanceof Node\Stmt\Nop) {
            return NodeTraverser::REMOVE_NODE;
        }

        // TRANSFORM: Remove inline HTML from representation
        if ($node instanceof InlineHTML) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
