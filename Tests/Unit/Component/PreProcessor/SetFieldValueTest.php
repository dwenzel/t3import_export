<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
use CPSIT\T3importExport\Component\PreProcessor\SetFieldValue;
use PHPUnit\Framework\TestCase;

/**
 * Class SetFieldValueTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\PreProcessor
 */
class SetFieldValueTest extends TestCase
{
    /**
     * @var SetFieldValue
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            SetFieldValue::class, ['dummy']
        );
    }

    /**
     * Data provider for configuration validation
     *
     * @return array
     */
    public function validateConfigurationDataProvider()
    {
        return [
            // empty targetField
            [[], false],
            [
                // empty value field
                [
                    'targetField' => 'foo'
                ],
                false
            ],
            [
                // target field is not string
                [
                    'targetField' => [],
                    'value' => []
                ],
                false
            ],
            [
                [
                    'targetField' => 'foo',
                    'value' => 'bar'
                ],
                true
            ]
        ];
    }

    /**
     * @test
     * @dataProvider validateConfigurationDataProvider
     * @param array $configuration
     * @param bool $result
     */
    public function configurationIsValidatedCorrectly($configuration, $result)
    {
        $this->assertEquals(
            $result,
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function processSetsValue()
    {
        $targetFieldName = 'foo';
        $newValue = 'baz';
        $configuration = [
            'targetField' => $targetFieldName,
            'value' => 'baz'
        ];
        $record = [
            $targetFieldName => 'bar'
        ];
        $expectedRecord = [
            $targetFieldName => $newValue
        ];

        $this->subject->process($configuration, $record);
        $this->assertSame(
            $record,
            $expectedRecord
        );
    }
}
