<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\MapFieldValues;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

#[CoversClass(MapFieldValues::class)]
class MapFieldValuesTest extends TestCase
{
    protected MapFieldValues $subject;

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->subject = new MapFieldValues();
    }

    public function testIsConfigurationValidReturnsInitiallyFalse(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotArray(): void
    {
        $config = [
            'fields' => 'foo',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotSet(): void
    {
        $config = [
            'fields' => [
                'foo' => ['bar'],
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotString(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 99,
                ],
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfValuesIsNotSet(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 'bar',
                ],
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfValuesIsNotArray(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 'bar',
                    'values' => 'illegalStringValue',
                ],
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 'bar',
                    'values' => [
                        'baz' => 0,
                    ],
                ],
            ],
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * Values are compared loosely (`==`); if several values match, the last one wins
     */
    public static function processDataProvider(): iterable
    {
        $configuration = [
            'fields' => [
                'foo' => [
                    'targetField' => 'foo',
                    'values' => [
                        'bar' => 'baz',
                    ],
                ],
            ],
        ];

        yield 'matching value is replaced' => [
            $configuration,
            ['foo' => 'bar'],
            ['foo' => 'baz'],
        ];
        yield 'non matching value is kept' => [
            $configuration,
            ['foo' => 'boom'],
            ['foo' => 'boom'],
        ];
        yield 'value is written to target field' => [
            ['fields' => ['type' => ['targetField' => 'label', 'values' => ['1' => 'Course', '2' => 'Event']]]],
            ['type' => '2'],
            ['type' => '2', 'label' => 'Event'],
        ];
        yield 'target field is not set if no value matches' => [
            ['fields' => ['type' => ['targetField' => 'label', 'values' => ['1' => 'Course']]]],
            ['type' => '3'],
            ['type' => '3'],
        ];
        yield 'integer matches numeric string key' => [
            ['fields' => ['type' => ['targetField' => 'label', 'values' => ['1' => 'Course']]]],
            ['type' => 1],
            ['type' => 1, 'label' => 'Course'],
        ];
        yield 'numeric strings are compared as numbers, last match wins' => [
            ['fields' => ['type' => ['targetField' => 'label', 'values' => ['1' => 'first', '01' => 'second']]]],
            ['type' => '1'],
            ['type' => '1', 'label' => 'second'],
        ];
        yield 'null matches empty string key' => [
            ['fields' => ['type' => ['targetField' => 'label', 'values' => ['' => 'none']]]],
            ['type' => null],
            ['type' => null, 'label' => 'none'],
        ];
        yield 'mapped value may be of any type' => [
            ['fields' => ['hidden' => ['targetField' => 'hidden', 'values' => ['yes' => 1, 'no' => 0]]]],
            ['hidden' => 'no'],
            ['hidden' => 0],
        ];
        yield 'several fields' => [
            [
                'fields' => [
                    'a' => ['targetField' => 'a', 'values' => ['x' => 'X']],
                    'b' => ['targetField' => 'c', 'values' => ['y' => 'Y']],
                ],
            ],
            ['a' => 'x', 'b' => 'y'],
            ['a' => 'X', 'b' => 'y', 'c' => 'Y'],
        ];
    }

    #[Test]
    #[DataProvider('processDataProvider')]
    public function processMapsFieldValues(array $configuration, array $record, array $result): void
    {
        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame($result, $record);
    }
}
