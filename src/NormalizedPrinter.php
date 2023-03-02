<?php

declare(strict_types=1);

namespace App;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Scalar;
use PhpParser\PrettyPrinter\Standard;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

/**
 * Normalized representation of the AST
 *
 * Basically, this class is a copy of the Standard pretty printer with some modifications:
 * - Every method that uses `getAttribute()` is normalized
 */
class NormalizedPrinter extends Standard
{
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
    protected function pScalar_String(Scalar\String_ $node): string
    {
        return parent::pScalar_String($node); // TODO: normalize strings representation (quotes, HEREDOC, etc.)
    }

    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
    protected function pScalar_Encapsed(Scalar\Encapsed $node): string
    {
        return parent::pScalar_Encapsed($node); // TODO: normalize encapsed strings representation
    }

    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
    protected function pScalar_LNumber(Scalar\LNumber $node): string
    {
        return parent::pScalar_LNumber($node); // TODO: normalize number representation
    }

    protected function pExpr_Cast_Double(Cast\Double $node): string
    {
        // Everything is a double in PHP (no float nor real)
        return $this->pPrefixOp(Cast\Double::class, '(double) ', $node->expr);
    }

    protected function pExpr_Array(Expr\Array_ $node): string
    {
        // TRANSFORM: Normalize array
        return '[' . $this->pMaybeMultiline($node->items, true) . ']';
    }

    protected function pExpr_Exit(Expr\Exit_ $node): string
    {
        // TRANSFORM: `die` is an alias for `exit`
        return 'exit' . ($node->expr !== null ? '(' . $this->p($node->expr) . ')' : '');
    }
}
