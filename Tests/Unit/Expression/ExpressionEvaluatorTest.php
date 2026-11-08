<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Expression;

use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\T3importExport\Expression\ExpressionEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExpressionEvaluatorTest extends TestCase
{
    protected ExpressionEvaluator $subject;

    protected function setUp(): void
    {
        $this->subject = new ExpressionEvaluator();
    }

    #[Test]
    public function evaluateReturnsValueOfExpression(): void
    {
        $record = ['teaser' => '', 'abstract' => 'from abstract'];

        self::assertSame(
            'from abstract',
            $this->subject->evaluate("record['teaser'] ?: record['abstract']", ['record' => $record])
        );
    }

    #[Test]
    public function evaluateReturnsDefaultForMissingKeyWithNullCoalescing(): void
    {
        self::assertSame(
            'default',
            $this->subject->evaluate("record['missing'] ?? 'default'", ['record' => []])
        );
    }

    #[Test]
    public function evaluateThrowsExceptionForInvalidExpression(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(ExpressionEvaluator::ERROR_INVALID_EXPRESSION);

        $this->subject->evaluate("record['type'] ==", ['record' => []]);
    }

    #[Test]
    public function evaluateThrowsExceptionForUnknownVariable(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->subject->evaluate('unknown == 1');
    }

    /**
     * @return array<string, array{string, array<string, mixed>, bool}>
     */
    public static function isTrueDataProvider(): array
    {
        return [
            'comparison true' => ["record['type'] == 'news'", ['type' => 'news'], true],
            'comparison false' => ["record['type'] == 'news'", ['type' => 'event'], false],
            'non-empty string' => ["record['title']", ['title' => 'foo'], true],
            'empty string' => ["record['title']", ['title' => ''], false],
            'string zero' => ["record['hidden']", ['hidden' => '0'], false],
            'in list' => ["record['uid'] in [1, 2, 3]", ['uid' => 2], true],
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    #[DataProvider('isTrueDataProvider')]
    #[Test]
    public function isTrueReturnsBooleanValueOfExpression(string $expression, array $record, bool $expected): void
    {
        self::assertSame($expected, $this->subject->isTrue($expression, ['record' => $record]));
    }
}
