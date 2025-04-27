<?php

declare(strict_types=1);

namespace App;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

use function array_map;
use function array_push;
use function array_shift;
use function array_splice;
use function assert;
use function count;
use function is_string;

/**
 * Apply transformations to normalize the AST
 */
class NormalizeNodeVisitor extends NodeVisitorAbstract
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
     * TRANSFORM: Replace method name with stable name
     */
    private function replaceMethodName(ClassMethod $node): void
    {
        // TRANSFORM: Declare everything public
        $node->flags |= Modifiers::PUBLIC;
        $node->flags &= ~Modifiers::PRIVATE;
        $node->flags &= ~Modifiers::PROTECTED;
        // TRANSFORM: Remove final and readonly modifiers
        $node->flags &= ~Modifiers::FINAL;
        $node->flags &= ~Modifiers::READONLY;

        $node->name = new Identifier($this->mapping->addMethod($node->name->toString()));
    }

    /**
     * TRANSFORM: Replace static call name with stable name
     */
    private function replaceStaticCallName(StaticCall $node): void
    {
        $className = $node->class;
        if ($className instanceof Name) {
            $node->class = new Name($this->mapping->addClass($className->toString()));
        }

        $methodName = $node->name;
        if ($methodName instanceof Identifier) {
            $node->name = new Identifier($this->mapping->addMethod($methodName->toString()));
        }
    }

    /**
     * TRANSFORM: Replace method call name with stable name
     */
    private function replaceMethodCallName(MethodCall $node): void
    {
        $methodName = $node->name;
        if ($methodName instanceof Identifier) {
            $node->name = new Identifier($this->mapping->addMethod($methodName->toString()));
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
    private function normalizeInterpolatedString(InterpolatedString $string): Node
    {
        $parts = array_map(
            static fn (Node $part) => $part instanceof InterpolatedStringPart ? new String_($part->value) : $part,
            $string->parts,
        );

        $left = array_shift($parts);
        assert($left !== null, 'Interpolated string had 0 part.');
        while ($right = array_shift($parts)) {
            $left = new Concat($left, $right);
        }

        return $left;
    }

    /**
     * TRANSFORM: Simplify useless concat such as `'a' . 'b'` => `'ab'`
     */
    private function simplifyUselessConcat(Concat $concat): String_|Concat
    {
        // 1. Flatten Concat-tree to an array of nodes: Concat('0', Concat('1', $a)) => ['0','1',$a]
        $nodes = $this->unwrapConcat($concat);
        // 2. Merge consecutive String_: ['0','1',$a,'2','3',$b,'4','5'] => ['01',$a,'23',$b,'45']
        $index = count($nodes) - 1;
        while ($index > 0) {
            $left = $nodes[$index - 1];
            $right = $nodes[$index];
            if ($left instanceof String_ && $right instanceof String_) {
                array_splice($nodes, $index - 1, 2, [new String_($left->value . $right->value)]);
            }

            $index--;
        }

        // 3. Re-build a Concat-tree, left-based associativity to avoid extra-parentheses:
        // ['01',$a,'23'] => Concat(Concat('01', $a), '23')
        $node = array_shift($nodes);
        assert($node !== null, 'Concat has at least 1 node');
        while ($right = array_shift($nodes)) {
            $node = new Concat($node, $right);
        }

        assert(
            $node instanceof String_ || $node instanceof Concat,
            'Either everything was collapsed to a singled String_ or we have a top-level Concat remaining',
        );

        return $node;
    }

    /**
     * Unwrap a tree of Concat to a flat array of nodes
     *
     * @return list<Node\Expr>
     */
    private function unwrapConcat(Concat $concat): array
    {
        $nodes = [];
        if ($concat->left instanceof Concat) {
            array_push($nodes, ...$this->unwrapConcat($concat->left));
        } else {
            $nodes[] = $concat->left;
        }

        if ($concat->right instanceof Concat) {
            array_push($nodes, ...$this->unwrapConcat($concat->right));
        } else {
            $nodes[] = $concat->right;
        }

        return $nodes;
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
     * TRANSFORM: remove class const type
     */
    private function removeClassConstType(ClassConst $node): void
    {
        $node->type = null;
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
        } elseif ($node instanceof InterpolatedString) {
            return $this->normalizeInterpolatedString($node);
        } elseif ($node instanceof Array_) {
            $this->normalizeArray($node);
        } elseif ($node instanceof Exit_) {
            $this->normalizeExit($node);
        } elseif ($node instanceof Double) {
            $this->normalizeCastDouble($node);
        } elseif ($node instanceof ClassMethod) {
            $this->replaceMethodName($node);
        } elseif ($node instanceof StaticCall) {
            $this->replaceStaticCallName($node);
        } elseif ($node instanceof MethodCall) {
            $this->replaceMethodCallName($node);
        } elseif ($node instanceof ClassConst) {
            $this->removeClassConstType($node);
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
            return NodeVisitor::REMOVE_NODE;
        }

        // TRANSFORM: Remove inline HTML from representation
        if ($node instanceof InlineHTML) {
            return NodeVisitor::REMOVE_NODE;
        }

        return null;
    }
}
