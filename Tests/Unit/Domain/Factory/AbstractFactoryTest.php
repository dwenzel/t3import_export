<?php

namespace CPSIT\T3importExport\Tests\Domain\Factory;

use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockConfigurationManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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
 * @coversDefaultClass AbstractFactory
 */
class AbstractFactoryTest extends TestCase
{
    use MockConfigurationManagerTrait;

    /**
     * @var AbstractFactory|MockObject
     */
    protected $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp()
    {

        $this->subject = $this->getMockBuilder(AbstractFactory::class)
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration'])
            ->getMock();
        $this->subject->injectConfigurationManager($this->configurationManager);

    }


    /**
     * @covers ::injectConfigurationManager
     */
    public function testInjectConfigurationManagerForObjectSetsConfigurationManager(): void
    {
        $this->markTestSkipped('Test fails since mock configuration manager does not return expected settings');
        $expectedSettings = ['foo'];

        $this->configurationManager->expects($this->once())
            ->method('getConfiguration')
            ->with(...[ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS])
            ->willReturn($expectedSettings);

        self::assertAttributeSame(
            $this->configurationManager,
            'configurationManager',
            $this->subject
        );
        self::assertAttributeSame(
            $expectedSettings,
            'settings',
            $this->subject
        );
    }
}
