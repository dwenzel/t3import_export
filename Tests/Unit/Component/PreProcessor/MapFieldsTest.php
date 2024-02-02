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

use CPSIT\T3importExport\Component\PreProcessor\MapFields;
use PHPUnit\Framework\TestCase;

/**
 * Class MapFieldsTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\MapFields
 */
class MapFieldsTest extends TestCase
{

    protected MapFields $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        $this->subject = new MapFields();
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
    public function testIsConfigurationValidReturnsFalseIfFieldValueIsNotString(): void
    {
        $config = [
            'fields' => [
                'foo' => 0
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldValueIsEmpty(): void
    {
        $config = [
            'fields' => [
                'foo' => ''
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
                'foo' => 'bar',
                'baz' => 'fooBar'
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function processMapsFields(): void
    {
        $config = [
            'fields' => [
                'firstSourceField' => 'firstTargetField',
                'secondSourceField' => 'secondTargetField'
            ]
        ];
        $record = [
            'firstSourceField' => 'firstValue',
            'secondSourceField' => 'secondValue'
        ];
        $expectedResult = [
            'firstSourceField' => 'firstValue',
            'secondSourceField' => 'secondValue',
            'firstTargetField' => 'firstValue',
            'secondTargetField' => 'secondValue'
        ];
        $this->subject->process($config, $record);

        $this->assertEquals(
            $expectedResult,
            $record
        );
    }
}
