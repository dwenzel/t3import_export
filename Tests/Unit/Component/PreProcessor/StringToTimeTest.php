<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

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


use CPSIT\T3importExport\Component\PreProcessor\StringToTime;
use PHPUnit\Framework\TestCase;

/**
 * Class StringToTimeTest
 * @package CPSIT\T3importExport\Tests\Unit\Component\PreProcessor
 */
class StringToTimeTest extends TestCase
{
    protected StringToTime $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp()
    {
        $this->subject = new StringToTime();
    }

    /**
     * Data provider for method isConfigurationValid
     *
     * @return array
     */
    public function isConfigurationValidDataProvider(): array
    {
        return [
            [['foo'], false],
            [['fields' => 'foo'], true]
        ];
    }

    /**
     * @dataProvider isConfigurationValidDataProvider
     * @param array $configuration
     * @param bool $expectedValue
     */
    public function testIsConfigurationValidReturnsCorrectValues(array $configuration, bool $expectedValue): void
    {
        $this->assertSame(
            $expectedValue,
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testProcessConvertsFields(): void
    {
        $configuration = [
            'fields' => 'foo,bar'
        ];
        $record = [
            'foo' => 'now',
            'baz' => 'boo'
        ];
        $expectedRecord = [
            'foo' => strtotime($record['foo']),
            'baz' => 'boo',
            'bar' => null
        ];
        $this->subject->process($configuration, $record);
        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    /**
     * @test
     */
    public function processConvertsMultipleRowFields(): void
    {
        $configuration = [
            'fields' => 'foo',
            'multipleRowFields' => 'bar'
        ];
        $record = [
            'bar' => [
                ['foo' => 'now']
            ]
        ];
        $expectedRecord = [
            'bar' => [
                ['foo' => strtotime($record['bar'][0]['foo'])]
            ],
            'foo' => null,
        ];
        $this->subject->process($configuration, $record);
        $this->assertSame(
            $expectedRecord,
            $record
        );
    }
}
