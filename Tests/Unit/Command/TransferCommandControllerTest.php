<?php

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\T3importExport\Command\TransferCommandController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Tests\Unit\Persistence\MockModelObject;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

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
    use MockObjectManagerTrait;

    /**
     * @var TransferCommandController
     */
    protected $subject;

    /**
     * @var DataTransferProcessor|MockObject
     */
    protected $transferProcessor;

    /**
     * @var TransferTaskFactory|MockObject
     */
    protected $transferTaskFactory;

    /**
     * @var TransferSetFactory|MockObject
     */
    protected $transferSetFactory;

    /**
     * @var ConfigurationManagerInterface|MockObject
     */
    protected $configurationManager;

    /**
     * set up
     */
    public function setUp()
    {
        $this->markTestSkipped('Todo: replace ExtbaseCommandController by Symfony Command');

        $this->transferProcessor = $this->getMockBuilder(DataTransferProcessor::class)
            ->setMethods(['buildQueue', 'process'])
            ->getMock();
        $this->transferTaskFactory = $this->getMockBuilder(TransferTaskFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->transferSetFactory = $this->getMockBuilder(TransferSetFactory::class)
            ->getMock();
        $this->configurationManager = $this->getMockForAbstractClass(ConfigurationManagerInterface::class);
        $this->mockObjectManager();

        $this->subject = new TransferCommandController();
        $this->subject->injectDataTransferProcessor($this->transferProcessor);
        $this->subject->injectTransferTaskFactory($this->transferTaskFactory);
        $this->subject->injectTransferSetFactory($this->transferSetFactory);
        $this->subject->injectConfigurationManager($this->configurationManager);
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
        $this->subject = $this->subject->withSettings($settings);

        $mockTask = $this->getMockBuilder(TransferTask::class)->getMock();

        $this->transferTaskFactory->expects($this->once())
            ->method('get')
            ->with($settings['tasks'][$identifier])
            ->willReturn($mockTask);

        /** @var DataTransferProcessor|\PHPUnit_Framework_MockObject_MockObject $dataTransferProcessor */
        $this->transferProcessor = $this->getMockBuilder(DataTransferProcessor::class)
            ->setMethods(['buildQueue', 'process'])
            ->disableOriginalConstructor()
            ->getMock();
        $task = 'foo';
        $mockDemand = $this->getMockBuilder(DemandInterface::class)->getMock();
        $this->transferProcessor->expects($this->once())
            ->method('buildQueue')
            ->with(...[$mockDemand]);
        $this->transferProcessor->expects($this->once())
            ->method('process')
            ->with(...[$mockDemand]);

        $this->objectManager->expects($this->once())
            ->method('get')
            ->willReturn($mockDemand);

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
        $this->subject = $this->subject->withSettings($settings);

        $mockSet = $this->getMockBuilder(TransferSet::class)
            ->setMethods(['getTasks'])->getMock();
        $mockSet->expects($this->once())
            ->method('getTasks')
            ->will($this->returnValue([]));

        $this->transferSetFactory->expects($this->once())
            ->method('get')
            ->with($settings['sets'][$identifier])
            ->willReturn($mockSet);

       $set = 'foo';
        $result = ['bar'];
        $mockDemand = $this->getMock(
            DemandInterface::class
        );
        $this->transferProcessor->expects($this->once())
            ->method('buildQueue')
            ->with($mockDemand);
        $this->transferProcessor->expects($this->once())
            ->method('process')
            ->with($mockDemand)
            ->willReturn($result);
        $this->objectManager->expects($this->once())
            ->method('get')
            ->willReturn($mockDemand);
        $this->subject->setCommand($set);
    }

}
