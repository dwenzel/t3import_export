<?php
namespace CPSIT\T3importExport\Tests\Unit\Component;

use CPSIT\T3importExport\Component\AbstractComponent;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
class AbstractComponentTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\AbstractComponent
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMockForAbstractClass(
            AbstractComponent::class, [], '', true, true, true, ['renderContent']
        );
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
     * @cover ::injectSignalSlotDispatcher
     */
    public function injectSignalSlotDispatcherSetsDispatcher()
    {
        $mockDispatcher = $this->getMock(
            Dispatcher::class
        );

        $this->subject->injectSignalSlotDispatcher($mockDispatcher);
        $this->assertSame(
            $mockDispatcher,
            $this->subject->_get('signalSlotDispatcher')
        );
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
        if (isset($configuration['disable']) && is_array($configuration['disable']))
        {
            $this->subject->expects($this->any())
                ->method('renderContent')
                ->with($record, $configuration['disable'])
                ->will($this->returnValue((string)$expectedResult));
        }

        $this->assertSame(
            $expectedResult,
            $this->subject->isDisabled($configuration, [])
        );
    }

    /**
     * @test
     */
    public function objectManagerCanBeInjected()
    {
        $mockObjectManager = $this->getMock(
            ObjectManager::class
        );

        $this->subject->injectObjectManager($mockObjectManager);

        $this->assertAttributeSame(
            $mockObjectManager,
            'objectManager',
            $this->subject
        );
    }
}
