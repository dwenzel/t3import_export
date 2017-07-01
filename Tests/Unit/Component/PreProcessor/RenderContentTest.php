<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use TYPO3\CMS\Core\Tests\UnitTestCase;

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
 * Class RenderContentTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\RenderContent
 */
class RenderContentTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\PreProcessor\RenderContent
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock('CPSIT\\T3importExport\\Component\\PreProcessor\\RenderContent',
            ['dummy'], [], '', false);
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsInitiallyFalse()
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldsIsNotArray()
    {
        $config = [
            'fields' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldValueIsNotString()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldValueIsEmpty()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $config = [
            'fields' => [
                'foo' => ['bar'],
                'baz' => ['fooBar']
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function processRendersContent()
    {
        $subject = $this->getAccessibleMock(
            'CPSIT\\T3importExport\\Component\\PreProcessor\\RenderContent',
            ['renderContent'], [], '', false);
        $record = [];
        $configuration = [
            'fields' => [
                'fooField' => [
                    '_typoScriptNodeValue' => 'TEXT',
                    'value' => '1'
                ],
            ]
        ];

        $subject->expects($this->once())
            ->method('renderContent')
            ->with([], $configuration['fields']['fooField']);

        $subject->process($configuration, $record);
    }

    /**
     * @test
     */
    public function processRendersContentForMultipleRowFields()
    {
        $subject = $this->getAccessibleMock(
            'CPSIT\\T3importExport\\Component\\PreProcessor\\RenderContent',
            ['renderContent'], [], '', false);
        $record = [
            'fooField' => [
                [
                    'barField' => 'initialValue'
                ]
            ]
        ];
        $configuration = [
            'fields' => [
                'fooField' => [
                    'multipleRows' => '1',
                    'fields' => [
                        'barField' => [
                            '_typoScriptNodeValue' => 'TEXT',
                            'value' => '1'
                        ]
                    ]
                ],
            ]
        ];

        $subject->expects($this->once())
            ->method('renderContent');
        //->with($record['fooField'][0], $configuration['fields']['fooField']['fields']);

        $subject->process($configuration, $record);
    }
}
