<?php
namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingFinisher;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingInitializer;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingPostProcessor;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingPreProcessor;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
 * Class ImportCommandControllerTest
 *
 * @package CPSIT\T3importExport\Tests\Service
 * @coversDefaultClass \CPSIT\T3importExport\Service\DataTransferProcessor
 */
class DataTransferProcessorTest extends UnitTestCase
{
    const TASK_IDENTIFIER = 'fooBarBaz';

    /**
     * @var \CPSIT\T3importExport\Service\DataTransferProcessor|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var TaskResult|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taskResult;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var DemandInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $importDemand;

    /**
     * @var TransferTask|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transferTask;

    /**
     * @var array
     */
    protected $queue;

    /**
     * @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataSource;

    /**
     * @var DataTargetInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataTarget;

    /**
     * @var array
     */
    protected $records = [['foo']];

    /**
     * set up the subject
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            DataTransferProcessor::class,
            ['dummy'], [], '', false);
        $this->mockQueueWithSingleRecord();
        $this->mockDataSource();
        $this->mockDataTarget();

        $this->taskResult = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getMessages' , 'addMessages'])->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])->getMock();
        $this->subject->injectObjectManager($this->objectManager);
        $this->objectManager->method('get')->willReturn($this->taskResult);
        $this->importDemand = $this->getMockBuilder(DemandInterface::class)
            ->getMockForAbstractClass();
        $this->transferTask = $this->getMockBuilder(TransferTask::class)
            ->setMethods(['getIdentifier', 'getSource', 'getTarget',
                'getPreProcessors', 'getPostProcessors' , 'getInitializers', 'getFinishers'])->getMock();
        $this->transferTask->expects($this->any())->method('getIdentifier')
            ->willReturn(static::TASK_IDENTIFIER);

        $this->transferTask->method('getSource')->willReturn($this->dataSource);
        $this->transferTask->method('getTarget')->willReturn($this->dataTarget);

        $this->importDemand->method('getTasks')->willReturn([$this->transferTask]);
    }


    protected function mockQueueWithSingleRecord()
    {
        $this->queue = [
            static::TASK_IDENTIFIER => $this->records
        ];
        $this->inject(
            $this->subject,
            'queue',
            $this->queue
        );
    }

    protected function mockDataSource()
    {
        $this->dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->setMethods(['getRecords', 'getConfiguration'])
            ->getMockForAbstractClass();
        $sourceConfig = ['baz'];
        $this->dataSource->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($sourceConfig));
        $this->dataSource->method('getRecords')->willReturn($this->records);
    }

    protected function mockDataTarget()
    {
        $this->dataTarget = $this->getMockBuilder(DataTargetInterface::class)
            ->setMethods(['getRecords', 'getConfiguration'])
            ->getMockForAbstractClass();
        $targetConfig = ['baz'];
        $this->dataTarget->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($targetConfig));
        $this->dataTarget->method('getRecords')->willReturn($this->records);
    }

    /**
     * @test
     * @covers ::injectPersistenceManager
     */
    public function injectPersistenceManagerForObjectSetsPersistenceManager()
    {
        /** @var PersistenceManager $mockPersistenceManager */
        $mockPersistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
            [], [], '', false);

        $this->subject->injectPersistenceManager($mockPersistenceManager);

        $this->assertSame(
            $mockPersistenceManager,
            $this->subject->_get('persistenceManager')
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    public function createObjectManagerMock()
    {
        $this->taskResult = new TaskResult();

        $objectManagerMock = $this->getAccessibleMock(
            ObjectManager::class,
            ['get']
        );
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->with(TaskResult::class)
            ->will($this->returnValue($this->taskResult));

        $this->subject->injectObjectManager($objectManagerMock);

        return $objectManagerMock;
    }

    /**
     * @test
     * @covers ::injectObjectManager
     */
    public function injectObjectManagerForObjectSetsObjectManager()
    {
        $objectManagerMock = $this->createObjectManagerMock();

        $this->assertSame(
            $objectManagerMock,
            $this->subject->_get('objectManager')
        );
    }

    /**
     * @test
     * @covers ::getQueue
     */
    public function getQueueForArrayReturnsInitiallyEmptyArray()
    {
        $this->subject = $this->getMockBuilder(DataTransferProcessor::class)
            ->setMethods(['dummy'])->getMock();
        $this->assertSame(
            [],
            $this->subject->getQueue()
        );
    }

    /**
     * @test
     */
    public function buildQueueSetsQueue()
    {
        $this->subject->buildQueue($this->importDemand);

        $this->assertSame(
            $this->queue,
            $this->subject->getQueue()
        );
    }

    /**
     * @test
     */
    public function processInitiallyReturnsEmptyIterator()
    {
        $taskDemand = $this->getMock(
            TaskDemand::class, ['getTasks']
        );
        $taskDemand->expects($this->any())
            ->method('getTasks')
            ->will($this->returnValue([]));

        $this->createObjectManagerMock();

        $this->assertSame(
            $this->taskResult->toArray(),
            $this->subject->process($taskDemand)
        );
    }

    /**
     * @test
     */
    public function processDoesNotRunEmptyQueue()
    {
        $identifier = 'foo';
        $taskDemand = $this->getMock(
            TaskDemand::class, ['getTasks']
        );
        $mockTask = $this->getMock(
            TransferTask::class, ['getIdentifier']
        );

        $taskDemand->expects($this->any())
            ->method('getTasks')
            ->will($this->returnValue($mockTask));
        $mockTask->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($identifier));

        $this->createObjectManagerMock();

        $this->assertSame(
            $this->taskResult->toArray(),
            $this->subject->process($taskDemand)
        );
    }

    /**
     * @test
     */
    public function processPreProcesses()
    {
        $identifier = 'foo';
        $singleRecord = ['foo' => 'bar'];
        $queue = [
            $identifier => [$singleRecord]
        ];
        $this->subject->_set('queue', $queue);

        $taskDemand = $this->getMock(
            TaskDemand::class, ['getTasks']
        );
        $mockTask = $this->getMock(
            TransferTask::class, ['getIdentifier', 'getTarget', 'getPreProcessors']
        );
        $mockPreProcessor = $this->getMockForAbstractClass(
            PreProcessorInterface::class
        );
        $this->mockPersistenceManager();
        $this->createObjectManagerMock();

        $preProcessorConfig = ['foo'];

        $mockTarget = $this->getMock(
            DataTargetInterface::class
        );
        $taskDemand->expects($this->any())
            ->method('getTasks')
            ->will($this->returnValue([$mockTask]));
        $mockTask->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($identifier));
        $mockTask->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($mockTarget));
        $mockTask->expects($this->once())
            ->method('getPreProcessors')
            ->will($this->returnValue([$mockPreProcessor]));
        $mockPreProcessor->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($preProcessorConfig));
        $mockPreProcessor->expects($this->any())
            ->method('isDisabled')
            ->with($preProcessorConfig, $singleRecord)
            ->will($this->returnValue(false));
        $mockPreProcessor->expects($this->any())
            ->method('process')
            ->with($preProcessorConfig, $singleRecord);

        $this->subject->process($taskDemand);
    }

    /**
     * @test
     */
    public function processConverts()
    {
        $identifier = 'foo';
        $taskDemand = $this->getMock(
            TaskDemand::class, ['getTasks']
        );
        $this->mockPersistenceManager();
        $this->createObjectManagerMock();

        $singleRecord = ['foo' => 'bar'];
        $queue = [
            $identifier => [$singleRecord]
        ];
        $this->subject->_set('queue', $queue);
        $mockTask = $this->getMock(
            TransferTask::class, ['getIdentifier', 'getTarget', 'getConverters']
        );
        $mockTarget = $this->getMock(
            DataTargetInterface::class
        );
        $mockConverter = $this->getMockForAbstractClass(
            ConverterInterface::class
        );
        $convertersFromTask = [$mockConverter];
        $converterConfiguration = ['foo'];

        $taskDemand->expects($this->any())
            ->method('getTasks')
            ->will($this->returnValue([$mockTask]));
        $mockTask->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($identifier));
        $mockTask->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($mockTarget));
        $mockTask->expects($this->any())
            ->method('getConverters')
            ->will($this->returnValue($convertersFromTask));
        $mockConverter->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($converterConfiguration));
        $mockConverter->expects($this->any())
            ->method('isDisabled')
            ->with($converterConfiguration)
            ->will($this->returnValue(false));
        $mockConverter->expects($this->once())
            ->method('convert')
            ->with($singleRecord, $converterConfiguration)
            ->will($this->returnValue($singleRecord));


        $this->subject->process($taskDemand);
    }

    /**
     * @test
     */
    public function processPostProcesses()
    {
        $this->subject = $this->getAccessibleMock(
            DataTransferProcessor::class,
            ['convertSingle'], [], '', false);

        $identifier = 'foo';
        $singleRecord = ['foo' => 'bar'];
        $convertedRecord = $singleRecord;
        $records = [$singleRecord];
        $queue = [
            $identifier => $records
        ];
        $this->subject->_set('queue', $queue);
        $this->mockPersistenceManager();
        $this->createObjectManagerMock();

        $taskDemand = $this->getMock(
            TaskDemand::class, ['getTasks']
        );
        $mockTask = $this->getMock(
            TransferTask::class, ['getIdentifier', 'getTarget', 'getPostProcessors']
        );
        $mockTarget = $this->getMock(
            DataTargetInterface::class
        );
        $mockPostProcessor = $this->getMockForAbstractClass(
            PostProcessorInterface::class
        );
        $postProcessorConfiguration = ['foo'];
        $postProcessorsFromTask = [$mockPostProcessor];

        $this->subject->expects($this->once())
            ->method('convertSingle')
            ->will($this->returnValue($convertedRecord));
        $taskDemand->expects($this->any())
            ->method('getTasks')
            ->will($this->returnValue([$mockTask]));
        $mockTask->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($identifier));
        $mockTask->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($mockTarget));
        $mockTask->expects($this->any())
            ->method('getPostProcessors')
            ->will($this->returnValue($postProcessorsFromTask));
        $mockPostProcessor->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($postProcessorConfiguration));
        $mockPostProcessor->expects($this->any())
            ->method('isDisabled')
            ->with($postProcessorConfiguration)
            ->will($this->returnValue(false));
        $mockPostProcessor->expects($this->once())
            ->method('process')
            ->with($postProcessorConfiguration, $convertedRecord, $singleRecord);

        $this->subject->process($taskDemand);
    }

    /**
     * @test
     */
    public function processExecutesFinishers()
    {
        $identifier = 'foo';
        $queue = [
            $identifier => [
                ['bar']
            ]
        ];
        $mockTask = $this->getMock(
            TransferTask::class,
            ['getIdentifier', 'getTarget', 'getFinishers']
        );
        $mockFinisher = $this->getMockForAbstractClass(
            FinisherInterface::class
        );
        $finisherConfig = ['baz'];
        $mockDemand = $this->getMock(
                TaskDemand::class, ['getTasks']);
        $mockDemand->expects($this->once())
            ->method('getTasks')
            ->will($this->returnValue([$mockTask]));
        $mockTarget = $this->getMock(
                DataTargetInterface::class);
        $this->subject = $this->getAccessibleMock(
            DataTransferProcessor::class,
                ['convertSingle']
        );
        $this->subject->_set('queue', $queue);


        $mockTask->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($identifier));
        $mockTask->expects($this->once())
            ->method('getTarget')
            ->will($this->returnValue($mockTarget));
        $mockTask->expects($this->once())
            ->method('getFinishers')
            ->will($this->returnValue([$mockFinisher]));
        $mockFinisher->expects($this->once())
            ->method('isDisabled')
            ->will($this->returnValue(false));
        $mockFinisher->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($finisherConfig));
        $mockFinisher->expects($this->once())
            ->method('process')
            ->will($this->returnArgument(2));
        $mockPersistenceManager = $this->getMock(
                PersistenceManager::class, ['persistAll']
        );
        $this->subject->injectPersistenceManager(
                $mockPersistenceManager
        );

        $this->createObjectManagerMock();

        $this->subject->process($mockDemand);
    }

    /**
     * @test
     */
    public function processExecutesInitializers()
    {
        $identifier = 'foo';
        $queue = [
            $identifier => [
                ['bar']
            ]
        ];
        $mockTask = $this->getMock(
            TransferTask::class,
            ['getIdentifier', 'getTarget', 'getInitializers']
        );
        $mockInitializer = $this->getMockForAbstractClass(
            InitializerInterface::class
        );
        $initializerConfig = ['baz'];
        $mockDemand = $this->getMock(
            TaskDemand::class, ['getTasks']);
        $mockDemand->expects($this->once())
            ->method('getTasks')
            ->will($this->returnValue([$mockTask]));
        $mockTarget = $this->getMock(
            DataTargetInterface::class);
        $this->subject = $this->getAccessibleMock(
            DataTransferProcessor::class,
            ['convertSingle']
        );
        $this->subject->_set('queue', $queue);
        $mockTask->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue($identifier));
        $mockTask->expects($this->once())
            ->method('getTarget')
            ->will($this->returnValue($mockTarget));
        $mockTask->expects($this->once())
            ->method('getInitializers')
            ->will($this->returnValue([$mockInitializer]));
        $mockInitializer->expects($this->once())
            ->method('isDisabled')
            ->will($this->returnValue(false));
        $mockInitializer->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($initializerConfig));
        $mockInitializer->expects($this->once())
            ->method('process');
        $mockPersistenceManager = $this->getMock(
            PersistenceManager::class, ['persistAll']
        );
        $this->subject->injectPersistenceManager(
            $mockPersistenceManager
        );

        $this->createObjectManagerMock();

        $this->subject->process($mockDemand);
    }

    /**
     * injects and returns a mock persistence manager
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    public function mockPersistenceManager()
    {
        $mockPersistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['persistAll']
        );
        $this->subject->injectPersistenceManager($mockPersistenceManager);

        return $mockPersistenceManager;
    }

    /**
     * @test
     */
    public function processGathersMessagesFromLoggingPreProcessors() {
        $messages = ['foo'];
        $preProcessor = $this->getMockBuilder(LoggingPreProcessor::class)
            ->setMethods(['getMessages'])->getMock();
        $preProcessor->expects($this->once())
            ->method('getMessages')
            ->willReturn($messages);
        $this->transferTask->method('getPostProcessors')->willReturn([]);
        $this->transferTask->method('getInitializers')->willReturn([]);
        $this->transferTask->method('getFinishers')->willReturn([]);

        $this->transferTask->expects($this->atLeastOnce())
            ->method('getPreProcessors')
            ->willReturn([$preProcessor]);


        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->importDemand);
    }

    /**
     * @test
     */
    public function processGathersMessagesFromLoggingPostProcessors() {

        $messages = ['foo'];
        $postProcessor = $this->getMockBuilder(LoggingPostProcessor::class)
            ->setMethods(['getMessages'])->getMock();
        $postProcessor->expects($this->once())
            ->method('getMessages')
            ->willReturn($messages);

        $this->transferTask->method('getPreProcessors')->willReturn([]);
        $this->transferTask->method('getInitializers')->willReturn([]);
        $this->transferTask->method('getFinishers')->willReturn([]);

        $this->transferTask->expects($this->atLeastOnce())
            ->method('getPostProcessors')
            ->willReturn([$postProcessor]);

        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->importDemand);
    }

    /**
     * @test
     */
    public function processGathersMessagesFromLoggingInitializers() {

        $messages = ['foo'];
        $initializer = $this->getMockBuilder(LoggingInitializer::class)
            ->setMethods(['getMessages'])->getMock();
        $initializer->expects($this->once())
            ->method('getMessages')
            ->willReturn($messages);

        $this->transferTask->method('getPreProcessors')->willReturn([]);
        $this->transferTask->method('getPostProcessors')->willReturn([]);
        $this->transferTask->method('getFinishers')->willReturn([]);

        $this->transferTask->expects($this->atLeastOnce())
            ->method('getInitializers')
            ->willReturn([$initializer]);

        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->importDemand);
    }

    /**
     * @test
     */
    public function processGathersMessagesFromLoggingFinishers() {

        $messages = ['foo'];
        $finisher = $this->getMockBuilder(LoggingFinisher::class)
            ->setMethods(['getMessages'])->getMock();
        $finisher->expects($this->once())
            ->method('getMessages')
            ->willReturn($messages);

        $this->transferTask->method('getPreProcessors')->willReturn([]);
        $this->transferTask->method('getPostProcessors')->willReturn([]);
        $this->transferTask->method('getInitializers')->willReturn([]);

        $this->transferTask->expects($this->atLeastOnce())
            ->method('getFinishers')
            ->willReturn([$finisher]);

        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->importDemand);
    }

}
