<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component;

use CPSIT\T3importExport\Component\AbstractComponent;
use CPSIT\ImportExportCore\Expression\ExpressionEvaluator;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class AbstractComponentTest extends TestCase
{
    /**
     * @var AbstractComponent
     */
    protected $subject;

    /**
     * set up
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(AbstractComponent::class)
            ->onlyMethods(['isConfigurationValid'])
            ->getMock();
    }

    /**
     * Data provider for method isDisabled
     */
    public static function isDisabledDataProvider(): array
    {
        /** $configuration, $expectedValue */
        return [
            [
                [], false,
            ],
            [
                ['disable' => '1'], true,
            ],
            'integer 1 (YAML)' => [
                ['disable' => 1], true,
            ],
            'boolean true (YAML)' => [
                ['disable' => true], true,
            ],
            'boolean false (YAML)' => [
                ['disable' => false], false,
            ],
            'integer 0 (YAML)' => [
                ['disable' => 0], false,
            ],
            [
                ['disable' => ['foo']], false,
            ],
            [
                ['disable' => 'foo'], false,
            ],
        ];
    }

    #[DataProvider('isDisabledDataProvider')]
    #[Test]
    public function isDisabledReturnsCorrectValue(array $configuration, bool $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            $this->subject->isDisabled($configuration, [], null)
        );
    }

    public static function isDisabledReturnsTrueIfResultContainsMessageWithMatchingIdDataProvider(): array
    {
        return [
            'single message id' => [
                [
                    'disable' => [
                        'if' => [
                            'result' => [
                                'hasMessage' => '12345',
                            ],
                        ],
                    ],
                ],
            ],
            'multiple message ids' => [
                [
                    'disable' => [
                        'if' => [
                            'result' => [
                                'hasMessage' => '12345,2,7',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('isDisabledReturnsTrueIfResultContainsMessageWithMatchingIdDataProvider')]
    #[Test]
    public function isDisabledReturnsTrueIfResultContainsMessageWithMatchingId($configuration): void
    {
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['hasMessageWithId'])
            ->getMock();
        $result->expects($this->once())->method('hasMessageWithId')
            ->willReturn(true);
        $this->subject->isDisabled($configuration, [], $result);
    }

    /**
     * @return array<string, array{string, array<string, mixed>, bool}>
     */
    public static function isDisabledEvaluatesExpressionDataProvider(): array
    {
        return [
            'matching record' => ["record['type'] == 'news'", ['type' => 'news'], true],
            'not matching record' => ["record['type'] == 'news'", ['type' => 'event'], false],
            'without result' => ['result != null', [], false],
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    #[DataProvider('isDisabledEvaluatesExpressionDataProvider')]
    #[Test]
    public function isDisabledEvaluatesExpression(string $expression, array $record, bool $expected): void
    {
        $this->subject->injectExpressionEvaluator(new ExpressionEvaluator());

        self::assertSame(
            $expected,
            $this->subject->isDisabled(['disable' => ['expression' => $expression]], $record)
        );
    }

    #[Test]
    public function isDisabledPassesResultToExpression(): void
    {
        $this->subject->injectExpressionEvaluator(new ExpressionEvaluator());
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['hasMessageWithId'])
            ->getMock();
        $result->expects($this->once())->method('hasMessageWithId')
            ->with(5)
            ->willReturn(true);

        self::assertTrue(
            $this->subject->isDisabled(
                ['disable' => ['expression' => 'result.hasMessageWithId(5)']],
                [],
                $result
            )
        );
    }

    #[Test]
    public function isDisabledThrowsExceptionForContentObject(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(AbstractComponent::CONTENT_OBJECT_NOT_SUPPORTED_EXCEPTION_CODE);

        $this->subject->isDisabled(['disable' => ['_typoScriptNodeValue' => 'TEXT', 'value' => '1']]);
    }

    #[Test]
    public function isDisabledReturnsFalseIfNoMessageMatches(): void
    {
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['hasMessageWithId'])
            ->getMock();
        $result->method('hasMessageWithId')->willReturn(false);

        self::assertFalse(
            $this->subject->isDisabled(['disable' => ['if' => ['result' => ['hasMessage' => '3']]]], [], $result)
        );
    }
}
