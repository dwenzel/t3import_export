<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Expression;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides an expression evaluator, injected or created on first use
 */
trait ExpressionEvaluatorTrait
{
    protected ?ExpressionEvaluatorInterface $expressionEvaluator = null;

    public function injectExpressionEvaluator(ExpressionEvaluatorInterface $expressionEvaluator): void
    {
        $this->expressionEvaluator = $expressionEvaluator;
    }

    protected function getExpressionEvaluator(): ExpressionEvaluatorInterface
    {
        return $this->expressionEvaluator ??= GeneralUtility::makeInstance(ExpressionEvaluator::class);
    }
}
