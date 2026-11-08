<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;

/**
 * Sets fields of the record from expressions (Symfony ExpressionLanguage)
 *
 * The fields are evaluated in the configured order. Each expression gets the
 * variable `record`, which contains the values of fields set before.
 */
class EvaluateExpressions extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $record
     * @throws InvalidConfigurationException if an expression is invalid
     */
    public function process(array $configuration, array &$record): bool
    {
        foreach ($configuration['fields'] as $fieldName => $expression) {
            $record[$fieldName] = $this->getExpressionEvaluator()->evaluate(
                (string)$expression,
                ['record' => $record]
            );
        }

        return true;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[\Override]
    public function isConfigurationValid(array $configuration): bool
    {
        if (empty($configuration['fields']) || !is_array($configuration['fields'])) {
            return false;
        }
        foreach ($configuration['fields'] as $fieldName => $expression) {
            if (!is_string($fieldName) || !is_string($expression) || trim($expression) === '') {
                return false;
            }
        }

        return true;
    }
}
