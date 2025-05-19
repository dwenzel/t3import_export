<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

/**
 * This file is part of the "Import Export" project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Component\PreProcessor\UnsetEmptyFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class UnsetEmptyFieldsTest
 */
class UnsetEmptyFieldsTest extends TestCase
{
    /**
     * @var UnsetEmptyFields|MockObject
     */
    protected MockObject|UnsetEmptyFields $subject;

    /**
     * set up the subject
     */
    protected function setUp(): void
    {
        $this->subject = new UnsetEmptyFields();
    }

    /**
     * Data provider for configuration validation test
     */
    public static function configurationDataProvider(): array
    {
        return [
            // an empty configuration is invalid
            [[], false],
            [['foo'], false],
            [['fields' => ''], false],
            [['fields' => 'foo,bar'], true],
        ];
    }

    #[Test]
    #[DataProvider('configurationDataProvider')]
    public function isConfigurationValidReturnsCorrectResult($configuration, $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * Data provider for processing test
     */
    public static function processDataProvider(): array
    {
        return [
            [
                ['fields' => 'foo'], // configuration
                [], // incomingRecord
                [], // expectedResult
            ],
            [
                ['fields' => 'foo'],
                ['foo' => ''],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => 0],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => 0.0],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => "0"],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => null],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => false],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => []],
                [],
            ],
            [
                ['fields' => 'foo'],
                ['foo' => 'bar'],
                ['foo' => 'bar'],
            ],
            [
                ['fields' => 'foo'],
                ['bar' => ''],
                ['bar' => ''],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @param array $incomingRecord
     * @param array $expectedResult
     */
    #[Test]
    #[DataProvider('processDataProvider')]
    public function processUnsetsFieldsCorrectly(
        array $configuration,
        array $incomingRecord,
        array $expectedResult): void
    {
        $this->subject->process($configuration, $incomingRecord);

        $this->assertSame(
            $expectedResult,
            $incomingRecord
        );
    }
}
