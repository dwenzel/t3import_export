<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\T3importExport\Component\PreProcessor\EvaluateExpressions;
use CPSIT\T3importExport\Expression\ExpressionEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EvaluateExpressionsTest extends TestCase
{
    protected EvaluateExpressions $subject;

    protected function setUp(): void
    {
        $this->subject = new EvaluateExpressions();
        $this->subject->injectExpressionEvaluator(new ExpressionEvaluator());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'no fields' => [[]],
            'empty fields' => [['fields' => []]],
            'fields not an array' => [['fields' => 'title']],
            'numeric field name' => [['fields' => ["record['title']"]]],
            'expression not a string' => [['fields' => ['title' => ['foo']]]],
            'empty expression' => [['fields' => ['title' => ' ']]],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[DataProvider('invalidConfigurationDataProvider')]
    #[Test]
    public function isConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function isConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        self::assertTrue(
            $this->subject->isConfigurationValid(['fields' => ['teaser' => "record['teaser'] ?: record['abstract']"]])
        );
    }

    #[Test]
    public function processSetsFieldsFromExpressions(): void
    {
        $configuration = [
            'fields' => [
                'teaser' => "record['teaser'] ?: record['abstract']",
                'title' => "upper(trim(record['title'])) ~ ' (' ~ date('Y', record['starttime']) ~ ')'",
                'new' => "record['missing'] ?? 'default'",
            ],
        ];
        $record = [
            'teaser' => '',
            'abstract' => 'from abstract',
            'title' => ' annual report ',
            'starttime' => mktime(12, 0, 0, 6, 1, 2026),
        ];

        self::assertTrue($this->subject->process($configuration, $record));

        self::assertSame('from abstract', $record['teaser']);
        self::assertSame('ANNUAL REPORT (2026)', $record['title']);
        self::assertSame('default', $record['new']);
    }

    #[Test]
    public function processEvaluatesFieldsInOrder(): void
    {
        $configuration = [
            'fields' => [
                'first' => "'a'",
                'second' => "record['first'] ~ 'b'",
            ],
        ];
        $record = [];

        $this->subject->process($configuration, $record);

        self::assertSame('ab', $record['second']);
    }

    #[Test]
    public function processThrowsExceptionForInvalidExpression(): void
    {
        $configuration = ['fields' => ['title' => "record['title'] ~"]];
        $record = ['title' => 'foo'];

        $this->expectException(InvalidConfigurationException::class);

        $this->subject->process($configuration, $record);
    }
}
