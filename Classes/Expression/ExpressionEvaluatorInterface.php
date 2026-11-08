<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Expression;

use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;

/**
 * Evaluates expressions in configurations (conditions and computed values)
 */
interface ExpressionEvaluatorInterface
{
    /**
     * Returns the value of the expression
     *
     * @param array<string, mixed> $variables
     * @throws InvalidConfigurationException if the expression is invalid
     */
    public function evaluate(string $expression, array $variables = []): mixed;

    /**
     * Tells whether the expression evaluates to a true value
     *
     * @param array<string, mixed> $variables
     * @throws InvalidConfigurationException if the expression is invalid
     */
    public function isTrue(string $expression, array $variables = []): bool;
}
