<?php

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\T3importExport\Command\TransferCommandController;
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
 * @coversDefaultClass \CPSIT\T3importExport\Command\TransferCommandController
 */
class TransferCommandControllerTest extends TestCase
{

    /**
     * @var TransferCommandController
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            TransferCommandController::class, ['dummy']
        );
    }

    /**
     * @test
     * @covers ::injectDataTransferProcessor
     */
    public function injectDataTransferProcessorForObjectSetsDataTransferProcessor()
    {
        /** @var DataTransferProcessor $expectedProcessor */
        $expectedProcessor = $this->getMock(
            DataTransferProcessor::class);
        $this->subject->injectDataTransferProcessor($expectedProcessor);

        $this->assertAttributeSame(
            $expectedProcessor,
            'dataTransferProcessor',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectTransferTaskFactorySetsFactory()
    {
        /** @var TransferTaskFactory $factory */
        $factory = $this->getMock(
            TransferTaskFactory::class
        );
        $this->subject->injectTransferTaskFactory($factory);
        $this->assertAttributeSame(
            $factory,
            'transferTaskFactory',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectTransferSetFactorySetsFactory()
    {
        /** @var TransferSetFactory $factory */
        $factory = $this->getMock(
            TransferSetFactory::class
        );
        $this->subject->injectTransferSetFactory($factory);
        $this->assertAttributeSame(
            $factory,
            'transferSetFactory',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectConfigurationManagerSetsConfigurationManagerAndSettings()
    {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->getMock();
        $this->subject->injectConfigurationManager($configurationManager);

        $this->assertAttributeSame(
            $configurationManager,
            'configurationManager',
            $this->subject
        );
    }

    /**
     * @test
     * @covers ::taskCommand
     */
    public function taskCommandBuildsAndProcessesQueue()
    {
        $identifier = 'foo';
        $settings = [
            'tasks' => [
                $identifier => ['bar']
            ]
        ];
        $this->inject($this->subject, 'settings', $settings);

        $mockTask = $this->getMockBuilder(TransferTask::class)->getMock();
        /** @var TransferTaskFactory|\PHPUnit_Framework_MockObject_MockObject $transferTaskFactory */
        $transferTaskFactory = $this->getMockBuilder(TransferTaskFactory::class)
            ->setMethods(['get'])->getMock();
        $transferTaskFactory->expects($this->once())
            ->method('get')
            ->with($settings['tasks'][$identifier])
            ->will($this->returnValue($mockTask));
        $this->subject->injectTransferTaskFactory($transferTaskFactory);

        /** @var DataTransferProcessor|\PHPUnit_Framework_MockObject_MockObject $dataTransferProcessor */
        $dataTransferProcessor = $this->getMockBuilder(DataTransferProcessor::class)
            ->setMethods(['buildQueue', 'process'])
            ->disableOriginalConstructor()
            ->getMock();
        $task = 'foo';
        $mockDemand = $this->getMockBuilder(DemandInterface::class)->getMock();
        $dataTransferProcessor->expects($this->once())
            ->method('buildQueue')
            ->with($mockDemand);
        $dataTransferProcessor->expects($this->once())
            ->method('process')
            ->with($mockDemand);
        $this->subject->injectDataTransferProcessor($dataTransferProcessor);
        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])->getMock();
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockDemand));

        // we have to use magic method here since parents injection method calls get-Method of dependency!
        /** @noinspection PhpUndefinedMethodInspection */
        $this->subject->_set(
            'objectManager',
            $mockObjectManager
        );

        $this->subject->taskCommand($task);
    }

    /**
     * @test
     * @covers ::setCommand
     */
    public function setCommandBuildsAndProcessesQueue()
    {
        $identifier = 'foo';
        $settings = [
            'sets' => [
                $identifier => ['bar']
            ]
        ];
        $this->inject($this->subject, 'settings', $settings);
        $mockSet = $this->getMock(
            TransferSet::class, ['getTasks']
        );
        $mockSet->expects($this->once())
            ->method('getTasks')
            ->will($this->returnValue([]));

        /** @var TransferSetFactory|\PHPUnit_Framework_MockObject_MockObject $transferSetFactory */
        $transferSetFactory = $this->getMock(
            TransferSetFactory::class, ['get']
        );
        $transferSetFactory->expects($this->once())
            ->method('get')
            ->with($settings['sets'][$identifier])
            ->will($this->returnValue($mockSet));
        $this->subject->injectTransferSetFactory($transferSetFactory);
        /** @var DataTransferProcessor|\PHPUnit_Framework_MockObject_MockObject $dataTransferProcessor */
        $dataTransferProcessor = $this->getMock(
            DataTransferProcessor::class,
            ['buildQueue', 'process'], [], '', false
        );
        $set = 'foo';
        $result = ['bar'];
        $mockDemand = $this->getMock(
            DemandInterface::class
        );
        $dataTransferProcessor->expects($this->once())
            ->method('buildQueue')
            ->with($mockDemand);
        $dataTransferProcessor->expects($this->once())
            ->method('process')
            ->with($mockDemand)
            ->will($this->returnValue($result));
        $this->subject->injectDataTransferProcessor($dataTransferProcessor);
        $mockObjectManager = $this->getMock(
            ObjectManager::class,
            ['get']);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockDemand));
        // we have to use magic method here since parents injection method calls get-Method of dependency!
        /** @noinspection PhpUndefinedMethodInspection */
        $this->subject->_set(
            'objectManager',
            $mockObjectManager
        );


        $this->subject->setCommand($set);
    }
}
