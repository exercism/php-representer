<?php

declare(strict_types=1);

namespace App;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

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
        } elseif ($node instanceof Array_) {
            // TRANSFORM: Use short array syntax
            $node->setAttribute('kind', Array_::KIND_SHORT);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        // TRANSFORM: Remove empty statements from representation
        if ($node instanceof Node\Stmt\Nop) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
