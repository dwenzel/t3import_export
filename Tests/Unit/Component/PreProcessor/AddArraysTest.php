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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use CPSIT\T3importExport\Component\PreProcessor\AddArrays;

/**
 * Class AddArraysTest
 */
class AddArraysTest extends UnitTestCase
{
    /**
     * @var AddArrays
     */
    protected $subject;

    /**
     * Set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            AddArrays::class, ['dummy']
        );
    }

    /**
     * Data provider for configuration validation
     *
     * @return array
     */
    public function isConfigurationValidDataProvider()
    {
        return [
            [
                // configuration must not be empty
                [],
                false
            ],
            [
                // targetField must contain string
                [
                    'targetField' => []
                ],
                false
            ],
            [
                // fields must not be empty
                [
                    'targetField' => 'foo'
                ]
                ,
                false
            ],
            [
                // fields must contain string
                [
                    'targetField' => 'foo',
                    'fields' => [],
                ],
                false
            ],
            [
                // valid configuration
                [
                'targetField' => 'foo',
                'fields' => 'bar,baz',
                ],
                true
            ]
        ];
    }
    /**
     * @test
     * @dataProvider isConfigurationValidDataProvider
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
     * provides data for processing
     * @return array
     */
    public function processDataProvider()
    {
        return [
            [
                // empty record
                [], []
            ],
            [
                // no fields
                ['foo' => 'fooValue'], ['foo' => 'fooValue']
            ],
            [
                // field bar is not array - leave target field untouched
                [
                    'foo' => 'fooValue',
                    'bar' => 'barValue'
                ],
                [
                    'foo' => 'fooValue',
                    'bar' => 'barValue'
                ]
            ],
            [
                // single array in bar replaces empty array in foo
                [
                    'foo' => [],
                    'bar' => ['firstBarValue']
                ],
                [
                    'foo' => ['firstBarValue'],
                    'bar' => ['firstBarValue']
                ]
            ],
            [
                // multiple values in bar
                [
                    'foo' => [],
                    'bar' => ['firstBarValue', 'secondBarValue']
                ],
                [
                    'foo' => ['firstBarValue', 'secondBarValue'],
                    'bar' => ['firstBarValue', 'secondBarValue']
                ]
            ],
            [
                // duplicate values
                [
                    'foo' => [0],
                    'bar' => [0, 1]
                ],
                [
                    'foo' => [0, 0, 1],
                    'bar' => [0, 1]
                ]
            ],
            [
                // duplicate keys arr added!
                [
                    'foo' => ['key1' => 'valueFoo'],
                    'bar' => ['key1' => 'valueBar']
                ],
                [
                    'foo' => ['key1' => 'valueFoo', 'valueBar'],
                    'bar' => ['key1' => 'valueBar']
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider processDataProvider
     * @param array $record
     * $param array $result
     */
    public function processAddsArrays($record, $result)
    {
        $configuration = [
            'targetField' => 'foo',
            'fields' => 'bar,baz'
        ];

        $this->subject->process($configuration, $record);

        $this->assertEquals(
            $result,
            $record
        );
    }
}
