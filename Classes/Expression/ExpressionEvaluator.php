<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Expression;

use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Evaluates expressions with the Symfony ExpressionLanguage
 *
 * Additional functions: upper(), lower() (multibyte), trim(), date(), strtotime()
 *
 * @see https://symfony.com/doc/current/reference/formats/expression_language.html
 */
class ExpressionEvaluator implements ExpressionEvaluatorInterface
{
    public const ERROR_INVALID_EXPRESSION = 1794172347;

    protected ExpressionLanguage $expressionLanguage;

    public function __construct(?ExpressionLanguage $expressionLanguage = null)
    {
        $this->expressionLanguage = $expressionLanguage ?? new ExpressionLanguage();
        foreach (self::getPhpFunctions() as $name => $phpFunction) {
            $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp($phpFunction, $name));
        }
    }

    /**
     * Names of the expression functions and the PHP functions behind them
     *
     * @return array<string, string>
     */
    public static function getPhpFunctions(): array
    {
        return [
            'upper' => 'mb_strtoupper',
            'lower' => 'mb_strtolower',
            'trim' => 'trim',
            'date' => 'date',
            'strtotime' => 'strtotime',
        ];
    }

    public function evaluate(string $expression, array $variables = []): mixed
    {
        try {
            return $this->expressionLanguage->evaluate($expression, $variables);
        } catch (SyntaxError $error) {
            throw new InvalidConfigurationException(
                sprintf('Invalid expression "%s": %s', $expression, $error->getMessage()),
                self::ERROR_INVALID_EXPRESSION,
                $error
            );
        }
    }

    public function isTrue(string $expression, array $variables = []): bool
    {
        return (bool)$this->evaluate($expression, $variables);
    }
}
