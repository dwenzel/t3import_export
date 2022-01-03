<?php

namespace CPSIT\T3importExport\Tests\Unit\Component;

use CPSIT\T3importExport\Component\AbstractComponent;
use CPSIT\T3importExport\Domain\Model\TaskResult;
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
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(AbstractComponent::class)
            ->setMethods(['renderContent'])
            ->getMockForAbstractClass();
    }

    /**
     * Data provider for method isDisabled
     * @return array
     */
    public function isDisabledDataProvider()
    {
        /** $configuration, $expectedValue */
        return [
            [
                [], false,
            ],
            [
                ['disable' => '1'], true
            ],
            [
                ['disable' => ['foo']], true
            ],
            [
                ['disable' => 'foo'], false
            ]
        ];
    }

    /**
     * @test
     * @dataProvider isDisabledDataProvider
     * @param array $configuration
     * @param bool $expectedResult
     */
    public function isDisabledReturnsCorrectValue($configuration, $expectedResult)
    {
        $record = [];
        if (isset($configuration['disable']) && is_array($configuration['disable'])) {
            $this->subject->expects($this->any())
                ->method('renderContent')
                ->with($record, $configuration['disable'])
                ->will($this->returnValue((string)$expectedResult));
        }

        $this->assertSame(
            $expectedResult,
            $this->subject->isDisabled($configuration, [], null)
        );
    }

    public function isDisabledReturnsTrueIfResultContainsMessageWithMatchingIdDataProvider()
    {
        return [
            'single message id' => [
                [
                    'disable' => [
                        'if' => [
                            'result' => [
                                'hasMessage' => '12345'
                            ]
                        ]
                    ]
                ],
            ],
            'multiple message ids' => [
                [
                    'disable' => [
                        'if' => [
                            'result' => [
                                'hasMessage' => '12345,2,7'
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @test
     * @param $configuration
     * @dataProvider isDisabledReturnsTrueIfResultContainsMessageWithMatchingIdDataProvider
     */
    public function isDisabledReturnsTrueIfResultContainsMessageWithMatchingId($configuration)
    {
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['hasMessageWithId'])
            ->getMock();
        $result->expects($this->once())->method('hasMessageWithId')
            ->willReturn(true);
        $this->subject->isDisabled($configuration, null, $result);
    }
}
