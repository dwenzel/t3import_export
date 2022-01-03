<?php

namespace CPSIT\T3importExport\Tests\Unit\Command;

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
use CPSIT\T3importExport\Command\ExportCommandController;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class ExportCommandControllerTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Command
 * @coversDefaultClass \CPSIT\T3importExport\Command\ExportCommandController
 */
class ExportCommandControllerTest extends TestCase
{
    /**
     * @var ExportCommandController
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = new ExportCommandController();
    }

    /**
     * @test
     */
    public function initializeObjectSetsSettingsFromFramework()
    {
        $dataTransferProcessorSettings = ['foo'];
        $extbaseFrameWorkConfig = [
            'settings' => [
                ExportCommandController::SETTINGS_KEY => $dataTransferProcessorSettings
            ]
        ];

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setMethods(['getConfiguration'])->getMock();

        $configurationManager->expects($this->once())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->will($this->returnValue($extbaseFrameWorkConfig));
        $this->subject->injectConfigurationManager($configurationManager);

        $this->subject->initializeObject();

        $this->assertAttributeSame(
            $dataTransferProcessorSettings,
            'settings',
            $this->subject
        );
    }

}
