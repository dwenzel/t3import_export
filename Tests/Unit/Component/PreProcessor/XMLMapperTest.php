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

use CPSIT\T3importExport\Component\PreProcessor\XMLMapper;
use PHPUnit\Framework\TestCase;

/**
 * Class XMLMapperTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\XMLMapper
 */
class XMLMapperTest extends TestCase
{
    protected XMLMapper $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp()
    {
        $this->subject = new XMLMapper();
    }

    public function testConfigurationIsEmpty(): void
    {
        $testConfig = [];

        $this->assertFalse(
            $this->subject->isConfigurationValid($testConfig)
        );
    }

    /**
     * @return array
     */
    public function isConfigurationInvalidDataProvider(): array
    {
        return [
            [
                [
                    'foo' => 'bar',
                    'bar' => []
                ]
            ],
            [
                [
                    'fields' => 'bar',
                    'otherShit' => true
                ]
            ],
            [
                [
                    'foo' => true,
                    'stuff' => '@something'
                ]
            ],
            [
                [
                    'fields' => [
                        'staticSub' => [
                            'foo' => false,
                        ]
                    ]
                ]
            ],
            [
                [
                    'fields' => [
                        'manyChildren' => [
                            'children' => [
                                'id' => false
                            ],
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider isConfigurationInvalidDataProvider
     * @param array $testConfig
     */
    public function testConfigurationIsInvalid(array $testConfig): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($testConfig)
        );
    }

    /**
     * @return array
     */
    public function isConfigurationValidDataProvider(): array
    {
        return [
            // empty is valid, only the key 'fields' is required
            [
                [
                    'fields' => [],
                    'otherShit' => true
                ]
            ],
            // recursion with list array
            [
                [
                    'fields' => [
                        'manyChildren' => [
                            'children' => [
                                'id' => '@attribute'
                            ]
                        ]
                    ]
                ]
            ],
            // recursion with assoc array
            [
                [
                    'fields' => [
                        'single' => [
                            'id' => '@attribute'
                        ]
                    ]
                ]
            ],
            // CDATA
            [
                [
                    'fields' => [
                        'element' => '@cdata'
                    ]
                ]
            ],
            // ADVANCED CDATA
            [
                [
                    'fields' => [
                        'element' => [
                            'content' => '@value|@cdata'
                        ]
                    ]
                ]
            ]
        ];
    }


    /**
     * @dataProvider isConfigurationValidDataProvider
     * @param array $testConfig
     */
    public function testConfigurationIsValid(array $testConfig): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($testConfig)
        );
    }

    /**
     * @return array
     */
    public function processWithValidConfigDataProvider(): array
    {
        return [
            // check attribute
            [
                [
                    'id' => 123
                ],
                [
                    'fields' => [
                        'id' => '@attribute'
                    ]
                ],
                [
                    '@attribute' => [
                        'id' => 123
                    ]
                ]
            ],
            // check separate row in 1 dimension
            [
                [
                    'setting' => [
                        'foo'
                    ]
                ],
                [
                    'fields' => [
                        'setting' => '@separateRow'
                    ]
                ],
                [
                    'setting' => [
                        'foo',
                        '@separateRow' => true
                    ]
                ]
            ],
            // check separate row in multi dimensions
            [
                [
                    'setting' => [
                        'foo'
                    ]
                ],
                [
                    'fields' => [
                        'setting' => [
                            '@separateRow' => true
                        ]
                    ]
                ],
                [
                    'setting' => [
                        'foo',
                        '@separateRow' => true
                    ]
                ]
            ],

            // check mapTo in sub element
            [
                [
                    'setting' => [
                        'foo'
                    ]
                ],
                [
                    'fields' => [
                        'setting' => [
                            'mapTo' => 'setup'
                        ]
                    ]
                ],
                [
                    'setting' => [
                        'foo',
                        '@mapTo' => 'setup'
                    ]
                ]
            ],
            // check mapTo in direct element
            [
                [
                    'foo' => 1
                ],
                [
                    'fields' => [
                        'foo' => [
                            'mapTo' => 'bar'
                        ]
                    ]
                ],
                [
                    'foo' => [
                        '@value' => 1,
                        '@mapTo' => 'bar'
                    ]
                ]
            ],

            // check value
            [
                [
                    'element' => [
                        'content' => 'fooBar'

                    ]
                ],
                [
                    'fields' => [
                        'element' => [
                            'content' => '@value'
                        ]
                    ]
                ],
                [
                    'element' => [
                        '@value' => 'fooBar',
                    ]
                ]
            ],

            // check simple CDATA
            [
                [
                    'element' => 'fooBar'
                ],
                [
                    'fields' => [
                        'element' => '@cdata'
                    ]
                ],
                [
                    'element' => [
                        '@value' => 'fooBar',
                        '@cdata' => true
                    ]
                ]
            ],

            // check complex simple CDATA
            [
                [
                    'element' => [
                        'content' => 'fooBar'

                    ]
                ],
                [
                    'fields' => [
                        'element' => [
                            'content' => '@value|@cdata'
                        ]
                    ]
                ],
                [
                    'element' => [
                        '@value' => 'fooBar',
                        '@cdata' => true
                    ]
                ]
            ],

            // check children element with mapTo and value
            [
                [
                    'element' => [
                        [
                            'foo' => 'bar'
                        ],
                        [
                            'foo' => 'bar'
                        ]
                    ]
                ],
                [
                    'fields' => [
                        'element' => [
                            'children' => [
                                'mapTo' => 'item',
                                'foo' => '@value'
                            ]
                        ]
                    ]
                ],
                [
                    'element' => [
                        [
                            '@value' => 'bar',
                            '@mapTo' => 'item'
                        ],
                        [
                            '@value' => 'bar',
                            '@mapTo' => 'item'
                        ]
                    ]
                ]
            ],
        ];
    }


    /**
     * @dataProvider processWithValidConfigDataProvider
     * @param array $testConfig
     * @param array $data
     * @param array $expectedData
     */
    public function testProcessWithValidConfig(array $data, array $testConfig, array $expectedData): void
    {
        $this->subject->process($testConfig, $data);
        $this->assertEquals($data, $expectedData);
    }
}
