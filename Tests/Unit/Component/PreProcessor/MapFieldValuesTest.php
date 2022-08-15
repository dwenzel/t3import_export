<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\MapFieldValues;
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

/**
 * Class GuessSeminarLanguageTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\MapFieldValues
 */
class MapFieldValuesTest extends TestCase
{

    protected MapFieldValues $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp(): void
    {
        $this->subject = new MapFieldValues();
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsInitiallyFalse(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotArray(): void
    {
        $config = [
            'fields' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotSet(): void
    {
        $config = [
            'fields' => [
                'foo' => ['bar']
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotString(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 99
                ]
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfValuesIsNotSet(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 'bar',
                ]
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfValuesIsNotArray(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 'bar',
                    'values' => 'illegalStringValue'
                ]
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $config = [
            'fields' => [
                'foo' => [
                    'targetField' => 'bar',
                    'values' => [
                        'baz' => 0
                    ]
                ]
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * provides data for testing process
     *
     * @return array
     */
    public function processDataProvider(): array
    {
        $configuration = [
            'fields' => [
                'foo' => [
                    'targetField' => 'foo',
                    'values' => [
                        'bar' => 'baz'
                    ]
                ]
            ]
        ];

        return [
            [
                //matching value is replaced
                $configuration,
                ['foo' => 'bar'],
                ['foo' => 'baz']
            ],
            [
                // non matching value is kept
                $configuration,
                ['foo' => 'boom'],
                ['foo' => 'boom']
            ]
        ];
    }

    /**
     * @dataProvider processDataProvider
     * @param array $configuration
     * @param array $record
     * @param array $result
     */
    public function processMapsFieldValues(array $configuration, array $record, array $result): void
    {
        $this->subject->process($configuration, $record);
        $this->assertEquals(
            $result,
            $record
        );
    }
}
