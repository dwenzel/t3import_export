<?php

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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

/**
 * Class ImportCommandControllerTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Command
 * @coversDefaultClass \CPSIT\T3importExport\Command\ImportSetCommand
 */
class ImportCommandControllerTest extends TestCase
{

    /**
     * @var ImportSetCommand
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->markTestSkipped('Todo: replace ExtbaseCommandController by Symfony Command');
        $this->subject = new ImportSetCommand();
    }

    /**
     * @test
     */
    public function initializeObjectSetsSettingsFromFramework()
    {
        $dataTransferProcessorSettings = ['foo'];
        $extbaseFrameWorkConfig = [
            'settings' => [
                ImportSetCommand::SETTINGS_KEY => $dataTransferProcessorSettings
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
