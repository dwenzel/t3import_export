<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Service;

use CPSIT\ImportExportCore\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\ImportExportCore\Component\Initializer\InitializerInterface;
use CPSIT\ImportExportCore\Component\PostProcessor\PostProcessorInterface;
use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingPreProcessor;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingPostProcessor;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingInitializer;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingFinisher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

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
 */
#[CoversClass(DataTransferProcessor::class)]
class DataTransferProcessorTest extends TestCase
{
    protected const string TASK_IDENTIFIER = 'fooBarBaz';
    protected const array CONVERTER_CONFIGURATION = ['fooConverterConfig'];
    protected const array POST_PROCESSOR_CONFIGURATION = ['fooPostProcessorConfig'];
    protected const array FINISHER_CONFIGURATION = ['fooFinisherConfig'];
    protected const array SINGLE_RECORD = ['foo' => 'bar'];
    protected const array CONVERTED_RECORD = ['fooConverted' => 'convertedBar'];
    protected const array QUEUE_WITH_RECORD = [
        self::TASK_IDENTIFIER => [
            self::SINGLE_RECORD,
        ],
    ];

    protected DataTransferProcessor $subject;

    /**
     * @var TaskResult|MockObject
     */
    protected TaskResult|MockObject $taskResult;

    /**
     * @var TransferTask|MockObject
     */
    protected TransferTask|MockObject $transferTask;

    /**
     * @var TaskDemand|MockObject
     */
    protected TaskDemand|MockObject $taskDemand;

    /**
     * @var DataSourceInterface|MockObject
     */
    protected DataSourceInterface|MockObject$dataSource;

    /**
     * @var DataTargetInterface|MockObject
     */
    protected DataTargetInterface|MockObject $dataTarget;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected PersistenceManagerInterface|MockObject $persistenceManager;

    protected array $records = [['foo']];

    /**
     * @var PreProcessorInterface|LoggingPreProcessor|MockObject
     */
    protected PreProcessorInterface|LoggingPreProcessor|MockObject $preProcessor;

    /**
     * @var PostProcessorInterface|LoggingPreProcessor|MockObject
     */
    protected PostProcessorInterface|LoggingPreProcessor|MockObject $postProcessor;

    /**
     * @var ConverterInterface|MockObject
     */
    protected ConverterInterface|MockObject $converter;

    /**
     * @var InitializerInterface|MockObject
     */
    protected InitializerInterface|MockObject $initializer;

    /**
     * @var FinisherInterface|MockObject
     */
    protected FinisherInterface|MockObject $finisher;

    /**
     * set up the subject
     */
    protected function setUp(): void
    {
        /** @noinspection PhpParenthesesCanBeOmittedForNewCallInspection */
        $this->subject = (new DataTransferProcessor())->withQueue(self::QUEUE_WITH_RECORD);
        $this->mockPreProcessor();
        $this->mockPostProcessor();
        $this->mockConverter();
        $this->mockDataSource();
        $this->mockDataTarget();
        $this->mockInitializer();
        $this->mockFinisher();
        $this->mockTransferTask();
        $this->mockTaskDemand();
        $this->mockTaskResult();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    protected function mockPreProcessor(): void
    {
        $this->preProcessor = $this->createMock(
            PreProcessorInterface::class
        );
    }

    protected function mockPostProcessor(): void
    {
        $this->postProcessor = $this->createMock(
            PostProcessorInterface::class
        );
        $this->postProcessor->method('getConfiguration')
            ->willReturn(self::POST_PROCESSOR_CONFIGURATION);
    }

    protected function mockConverter(): void
    {
        $this->converter = $this->createMock(ConverterInterface::class);
        $this->converter->method('getConfiguration')->willReturn(self::CONVERTER_CONFIGURATION);
        $this->converter->method('convert')->willReturn(self::CONVERTED_RECORD);
    }

    protected function mockDataSource(): void
    {
        $this->dataSource = $this->createMock(DataSourceInterface::class);
        // Remove reference to non-existent getConfiguration method
        $this->dataSource->method('getRecords')->willReturn($this->records);
    }

    protected function mockDataTarget(): void
    {
        $this->dataTarget = $this->createMock(DataTargetInterface::class);
    }
    protected function mockInitializer(): void
    {
        $this->initializer = $this->createMock(InitializerInterface::class);
    }

    protected function mockFinisher(): void
    {
        $this->finisher = $this->createMock(FinisherInterface::class);
    }

    protected function mockTransferTask(): void
    {
        $this->transferTask = $this->getMockBuilder(TransferTask::class)
            ->onlyMethods(
                [
                    'getIdentifier',
                    'getSource',
                    'getTarget',
                    'getPreProcessors',
                    'getPostProcessors',
                    'getInitializers',
                    'getFinishers',
                    'getConverters',
                ]
            )->getMock();
        $this->transferTask->method('getIdentifier')->willReturn(static::TASK_IDENTIFIER);
        $this->transferTask->method('getSource')->willReturn($this->dataSource);
        $this->transferTask->method('getInitializers')->willReturn([$this->initializer]);
        $this->transferTask->method('getPreProcessors')->willReturn([$this->preProcessor]);
        $this->transferTask->method('getPostProcessors')->willReturn([$this->postProcessor]);
        $this->transferTask->method('getConverters')->willReturn([$this->converter]);
        $this->transferTask->method('getSource')->willReturn($this->dataSource);
        $this->transferTask->method('getTarget')->willReturn($this->dataTarget);
        $this->transferTask->method('getFinishers')->willReturn([$this->finisher]);
    }

    protected function mockTaskDemand(): void
    {
        $this->taskDemand = $this->getMockBuilder(TaskDemand::class)
            ->onlyMethods(['getTasks'])
            ->getMock();

        $this->taskDemand->method('getTasks')
            ->willReturn([$this->transferTask]);
    }

    protected function mockTaskResult(): void
    {
        $this->taskResult = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getMessages', 'addMessages'])->getMock();
        GeneralUtility::addInstance(TaskResult::class, $this->taskResult);
    }

    #[Test]
    public function testProcessPreProcesses(): void
    {
        $preProcessorConfig = ['foo'];

        $this->preProcessor->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->willReturn($preProcessorConfig);
        $this->preProcessor->expects($this->atLeastOnce())
            ->method('isDisabled')
            ->with($preProcessorConfig, self::SINGLE_RECORD)
            ->willReturn(false);
        $this->preProcessor->expects($this->once())
            ->method('process')
            ->with($preProcessorConfig, self::SINGLE_RECORD);

        $this->subject->process($this->taskDemand);
    }

    #[Test]
    public function testProcessConverts(): void
    {
        $this->converter->expects($this->atLeastOnce())
            ->method('getConfiguration');
        $this->converter->expects($this->atLeastOnce())
            ->method('isDisabled')
            ->with(...[self::CONVERTER_CONFIGURATION, self::SINGLE_RECORD, $this->taskResult])
            ->willReturn(false);
        $this->converter->expects($this->once())
            ->method('convert')
            ->with(self::SINGLE_RECORD, self::CONVERTER_CONFIGURATION);

        $this->subject->process($this->taskDemand);
    }

    #[Test]
    public function testProcessPostProcesses(): void
    {
        $this->postProcessor->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->willReturn(self::POST_PROCESSOR_CONFIGURATION);
        $this->postProcessor->expects($this->atLeastOnce())
            ->method('isDisabled')
            ->with(self::POST_PROCESSOR_CONFIGURATION)
            ->willReturn(false);
        $this->postProcessor->expects($this->once())
            ->method('process')
            ->with(self::POST_PROCESSOR_CONFIGURATION, self::CONVERTED_RECORD, self::SINGLE_RECORD);

        $this->subject->process($this->taskDemand);
    }

    #[Test]
    public function testProcessExecutesFinishers(): void
    {
        $this->finisher->expects($this->once())
            ->method('isDisabled')
            ->willReturn(false);
        $this->finisher->expects($this->once())
            ->method('getConfiguration');
        $this->finisher->expects($this->once())
            ->method('process')
            ->willReturn(true);

        $this->subject->process($this->taskDemand);
    }

    #[Test]
    public function testProcessExecutesInitializers(): void
    {
        $this->initializer->expects($this->once())
            ->method('isDisabled')
            ->willReturn(false);
        $this->initializer->expects($this->once())
            ->method('getConfiguration');
        $this->initializer->expects($this->once())
            ->method('process');

        $this->subject->process($this->taskDemand);
    }

    #[Test]
    public function testProcessGathersMessagesFromLoggingPreProcessors(): void
    {
        $messages = ['Test message from preprocessor'];
        
        // Create a mock that extends LoggingPreProcessor (which implements LoggingInterface)
        $preProcessorMock = $this->getMockBuilder(LoggingPreProcessor::class)
            ->onlyMethods(['getAndPurgeMessages', 'process', 'isDisabled', 'getConfiguration'])
            ->getMock();
        
        $preProcessorMock->method('getAndPurgeMessages')->willReturn($messages);
        $preProcessorMock->method('isDisabled')->willReturn(false);
        $preProcessorMock->method('getConfiguration')->willReturn([]);
        $preProcessorMock->method('process')->willReturn(true);
        
        // Create a new transfer task mock specifically for this test
        $transferTaskMock = $this->getMockBuilder(TransferTask::class)
            ->onlyMethods(['getIdentifier', 'getSource', 'getTarget', 'getPreProcessors', 'getPostProcessors', 'getInitializers', 'getFinishers', 'getConverters'])
            ->getMock();
        
        $transferTaskMock->method('getIdentifier')->willReturn(static::TASK_IDENTIFIER);
        $transferTaskMock->method('getSource')->willReturn($this->dataSource);
        $transferTaskMock->method('getTarget')->willReturn($this->dataTarget);
        $transferTaskMock->method('getPreProcessors')->willReturn([$preProcessorMock]);
        $transferTaskMock->method('getPostProcessors')->willReturn([]);
        $transferTaskMock->method('getInitializers')->willReturn([]);
        $transferTaskMock->method('getFinishers')->willReturn([]);
        $transferTaskMock->method('getConverters')->willReturn([$this->converter]);
        
        // Update task demand to return our specific transfer task
        $taskDemandMock = $this->getMockBuilder(TaskDemand::class)
            ->onlyMethods(['getTasks'])
            ->getMock();
        $taskDemandMock->method('getTasks')->willReturn([$transferTaskMock]);
        
        // Mock TaskResult to verify messages are added
        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        
        $this->subject->process($taskDemandMock);
    }

    #[Test]
    public function testProcessGathersMessagesFromLoggingPostProcessors(): void
    {
        $messages = ['Test message from postprocessor'];
        
        // Create a mock that extends LoggingPostProcessor (which implements LoggingInterface)
        $postProcessorMock = $this->getMockBuilder(LoggingPostProcessor::class)
            ->onlyMethods(['getAndPurgeMessages', 'process', 'isDisabled', 'getConfiguration'])
            ->getMock();
        
        $postProcessorMock->method('getAndPurgeMessages')->willReturn($messages);
        $postProcessorMock->method('isDisabled')->willReturn(false);
        $postProcessorMock->method('getConfiguration')->willReturn([]);
        $postProcessorMock->method('process')->willReturn(true);
        
        // Create a new transfer task mock specifically for this test
        $transferTaskMock = $this->getMockBuilder(TransferTask::class)
            ->onlyMethods(['getIdentifier', 'getSource', 'getTarget', 'getPreProcessors', 'getPostProcessors', 'getInitializers', 'getFinishers', 'getConverters'])
            ->getMock();
        
        $transferTaskMock->method('getIdentifier')->willReturn(static::TASK_IDENTIFIER);
        $transferTaskMock->method('getSource')->willReturn($this->dataSource);
        $transferTaskMock->method('getTarget')->willReturn($this->dataTarget);
        $transferTaskMock->method('getPreProcessors')->willReturn([]);
        $transferTaskMock->method('getPostProcessors')->willReturn([$postProcessorMock]);
        $transferTaskMock->method('getInitializers')->willReturn([]);
        $transferTaskMock->method('getFinishers')->willReturn([]);
        $transferTaskMock->method('getConverters')->willReturn([$this->converter]);
        
        // Update task demand to return our specific transfer task
        $taskDemandMock = $this->getMockBuilder(TaskDemand::class)
            ->onlyMethods(['getTasks'])
            ->getMock();
        $taskDemandMock->method('getTasks')->willReturn([$transferTaskMock]);
        
        // Mock TaskResult to verify messages are added
        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        
        $this->subject->process($taskDemandMock);
    }

    #[Test]
    public function testProcessGathersMessagesFromLoggingInitializers(): void
    {
        $messages = ['Test message from initializer'];
        
        // Create a mock that extends LoggingInitializer (which implements LoggingInterface)
        $initializerMock = $this->getMockBuilder(LoggingInitializer::class)
            ->onlyMethods(['getAndPurgeMessages', 'process', 'isDisabled', 'getConfiguration'])
            ->getMock();
        
        $initializerMock->method('getAndPurgeMessages')->willReturn($messages);
        $initializerMock->method('isDisabled')->willReturn(false);
        $initializerMock->method('getConfiguration')->willReturn([]);
        $initializerMock->method('process')->willReturn(true);
        
        // Create a new transfer task mock specifically for this test
        $transferTaskMock = $this->getMockBuilder(TransferTask::class)
            ->onlyMethods(['getIdentifier', 'getSource', 'getTarget', 'getPreProcessors', 'getPostProcessors', 'getInitializers', 'getFinishers', 'getConverters'])
            ->getMock();
        
        $transferTaskMock->method('getIdentifier')->willReturn(static::TASK_IDENTIFIER);
        $transferTaskMock->method('getSource')->willReturn($this->dataSource);
        $transferTaskMock->method('getTarget')->willReturn($this->dataTarget);
        $transferTaskMock->method('getPreProcessors')->willReturn([]);
        $transferTaskMock->method('getPostProcessors')->willReturn([]);
        $transferTaskMock->method('getInitializers')->willReturn([$initializerMock]);
        $transferTaskMock->method('getFinishers')->willReturn([]);
        $transferTaskMock->method('getConverters')->willReturn([$this->converter]);
        
        // Update task demand to return our specific transfer task
        $taskDemandMock = $this->getMockBuilder(TaskDemand::class)
            ->onlyMethods(['getTasks'])
            ->getMock();
        $taskDemandMock->method('getTasks')->willReturn([$transferTaskMock]);
        
        // Mock TaskResult to verify messages are added
        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        
        $this->subject->process($taskDemandMock);
    }

    #[Test]
    public function testProcessGathersMessagesFromLoggingFinishers(): void
    {
        $messages = ['Test message from finisher'];
        
        // Create a mock that extends LoggingFinisher (which implements LoggingInterface)
        $finisherMock = $this->getMockBuilder(LoggingFinisher::class)
            ->onlyMethods(['getAndPurgeMessages', 'process', 'isDisabled', 'getConfiguration'])
            ->getMock();
        
        $finisherMock->method('getAndPurgeMessages')->willReturn($messages);
        $finisherMock->method('isDisabled')->willReturn(false);
        $finisherMock->method('getConfiguration')->willReturn([]);
        $finisherMock->method('process')->willReturn(true);
        
        // Create a new transfer task mock specifically for this test
        $transferTaskMock = $this->getMockBuilder(TransferTask::class)
            ->onlyMethods(['getIdentifier', 'getSource', 'getTarget', 'getPreProcessors', 'getPostProcessors', 'getInitializers', 'getFinishers', 'getConverters'])
            ->getMock();
        
        $transferTaskMock->method('getIdentifier')->willReturn(static::TASK_IDENTIFIER);
        $transferTaskMock->method('getSource')->willReturn($this->dataSource);
        $transferTaskMock->method('getTarget')->willReturn($this->dataTarget);
        $transferTaskMock->method('getPreProcessors')->willReturn([]);
        $transferTaskMock->method('getPostProcessors')->willReturn([]);
        $transferTaskMock->method('getInitializers')->willReturn([]);
        $transferTaskMock->method('getFinishers')->willReturn([$finisherMock]);
        $transferTaskMock->method('getConverters')->willReturn([$this->converter]);
        
        // Update task demand to return our specific transfer task
        $taskDemandMock = $this->getMockBuilder(TaskDemand::class)
            ->onlyMethods(['getTasks'])
            ->getMock();
        $taskDemandMock->method('getTasks')->willReturn([$transferTaskMock]);
        
        // Mock TaskResult to verify messages are added
        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        
        $this->subject->process($taskDemandMock);
    }
}
