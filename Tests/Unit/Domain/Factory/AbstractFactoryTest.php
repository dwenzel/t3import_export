<?php
namespace CPSIT\T3importExport\Tests\Domain\Factory;

use CPSIT\T3importExport\Factory\AbstractFactory;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
 * Class AbstractFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Domain\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Factory\AbstractFactory
 */
class AbstractFactoryTest extends TestCase
{

    /**
     * @var \CPSIT\T3importExport\Factory\AbstractFactory
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            \CPSIT\T3importExport\Factory\AbstractFactory::class, ['get'], [], '', false
        );
    }

    /**
     * @test
     * @covers ::injectObjectManager
     */
    public function injectObjectManagerForObjectSetsObjectManager()
    {
        /** @var ObjectManager $mockObjectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);

        $this->subject->injectObjectManager($mockObjectManager);

        $this->assertSame(
            $mockObjectManager,
            $this->subject->_get('objectManager')
        );
    }

    /**
     * @test
     * @covers ::injectConfigurationManager
     */
    public function injectConfigurationManagerForObjectSetsConfigurationManager()
    {
        /** @var ConfigurationManager $mockConfigurationManager */
        $mockConfigurationManager = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $mockSettings = ['foo'];

        $mockConfigurationManager->expects($this->once())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS)
            ->will($this->returnValue($mockSettings));

        $this->subject->injectConfigurationManager($mockConfigurationManager);
        $this->assertSame(
            $mockConfigurationManager,
            $this->subject->_get('configurationManager')
        );
        $this->assertSame(
            $mockSettings,
            $this->subject->_get('settings')
        );
    }
}
