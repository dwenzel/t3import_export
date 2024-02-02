<?php

namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingFinisher;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingInitializer;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingPostProcessor;
use CPSIT\T3importExport\Tests\Unit\Fixtures\LoggingPreProcessor;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

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
 * @coversDefaultClass DataTransferProcessor
 */
class DataTransferProcessorTest extends TestCase
{
    use MockObjectManagerTrait;

    protected const TASK_IDENTIFIER = 'fooBarBaz';
    protected const CONVERTER_CONFIGURATION = ['fooConverterConfig'];
    protected const POST_PROCESSOR_CONFIGURATION = ['fooPostProcessorConfig'];
    protected const FINISHER_CONFIGURATION = ['fooFinisherConfig'];
    protected const SINGLE_RECORD = ['foo' => 'bar'];
    protected const CONVERTED_RECORD = ['fooConverted' => 'convertedBar'];
    protected const QUEUE_WITH_RECORD = [
        self::TASK_IDENTIFIER => [
            self::SINGLE_RECORD
        ]
    ];

    protected DataTransferProcessor $subject;

    /**
     * @var TaskResult|MockObject
     */
    protected $taskResult;

    /**
     * @var TransferTask|MockObject
     */
    protected $transferTask;

    /**
     * @var TaskDemand|MockObject
     */
    protected TaskDemand $taskDemand;

    /**
     * @var DataSourceInterface|MockObject
     */
    protected $dataSource;

    /**
     * @var DataTargetInterface|MockObject
     */
    protected $dataTarget;

    /**
     * @var PersistenceManager|MockObject
     */
    protected PersistenceManager $persistenceManager;

    /**
     * @var array
     */
    protected array $records = [['foo']];

    /**
     * @var PreProcessorInterface|LoggingPreProcessor|MockObject
     */
    protected $preProcessor;

    /**
     * @var PostProcessorInterface|LoggingPreProcessor|MockObject
     */
    protected PostProcessorInterface $postProcessor;

    /**
     * @var ConverterInterface|MockObject
     */
    protected ConverterInterface $converter;

    /**
     * @var InitializerInterface|MockObject
     */
    protected InitializerInterface $initializer;

    /**
     * @var FinisherInterface|MockObject
     */
    protected FinisherInterface $finisher;

    /**
     * set up the subject
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->subject = (new DataTransferProcessor())->withQueue(self::QUEUE_WITH_RECORD);
        $this->mockPreProcessor();
        $this->mockPostProcessor();
        $this->mockConverter();
        $this->mockDataSource();
        $this->mockDataTarget();
        $this->mockPersistenceManager();
        $this->mockInitializer();
        $this->mockFinisher();
        $this->mockTransferTask();
        $this->mockTaskDemand();
        $this->mockTaskResult();
    }

    public function tearDown()
    {
        GeneralUtility::purgeInstances();
    }

    protected function mockPreProcessor(): void
    {
        $this->preProcessor = $this->getMockForAbstractClass(
            PreProcessorInterface::class
        );
    }

    protected function mockPostProcessor(): void
    {
        $this->postProcessor = $this->getMockForAbstractClass(
            PostProcessorInterface::class
        );
        $this->postProcessor->method('getConfiguration')
            ->willReturn(self::POST_PROCESSOR_CONFIGURATION);
    }

    protected function mockConverter(): void
    {
        $this->converter = $this->getMockForAbstractClass(ConverterInterface::class);
        $this->converter->method('getConfiguration')->willReturn(self::CONVERTER_CONFIGURATION);
        $this->converter->method('convert')->willReturn(self::CONVERTED_RECORD);
    }

    protected function mockDataSource(): void
    {
        $this->dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->setMethods(['getRecords', 'getConfiguration'])
            ->getMockForAbstractClass();
        $sourceConfig = ['baz'];
        $this->dataSource->method('getConfiguration')
            ->willReturn($sourceConfig);
        $this->dataSource->method('getRecords')->willReturn($this->records);
    }

    protected function mockDataTarget(): void
    {
        $this->dataTarget = $this->getMockBuilder(DataTargetInterface::class)
            ->setMethods(['getRecords', 'getConfiguration'])
            ->getMockForAbstractClass();
        $targetConfig = ['baz'];
        $this->dataTarget
            ->method('getConfiguration')
            ->willReturn($targetConfig);
        $this->dataTarget->method('getRecords')->willReturn($this->records);
    }

    protected function mockPersistenceManager(): void
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['persistAll'])
            ->getMock();

        $this->subject->injectPersistenceManager($this->persistenceManager);
    }

    protected function mockInitializer(): void
    {
        $this->initializer = $this->getMockForAbstractClass(InitializerInterface::class);
    }

    protected function mockFinisher(): void
    {

        $this->finisher = $this->getMockForAbstractClass(FinisherInterface::class);
    }

    protected function mockTransferTask(): void
    {
        $this->transferTask = $this->getMockBuilder(TransferTask::class)
            ->setMethods(
                [
                    'getIdentifier',
                    'getTasks',
                    'getSource',
                    'getTarget',
                    'getPreProcessors',
                    'getPostProcessors',
                    'getInitializers',
                    'getFinishers',
                    'getConverters'
                ])->getMock();
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
            ->setMethods(['getTasks'])
            ->getMock();

        $this->taskDemand->method('getTasks')
            ->willReturn([$this->transferTask]);
    }

    protected function mockTaskResult(): void
    {
        $this->taskResult = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getMessages', 'addMessages'])->getMock();
        GeneralUtility::addInstance(TaskResult::class, $this->taskResult);
    }


    public function testBuildQueueSetsQueue(): void
    {
        $expectedQueue = [
            $this->transferTask->getIdentifier() => $this->dataSource->getRecords(
                $this->dataSource->getConfiguration()
            )
        ];
        $this->subject->buildQueue($this->taskDemand);

        $this->assertSame(
            $expectedQueue,
            $this->subject->getQueue()
        );
    }

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

    public function testProcessGathersMessagesFromLoggingPreProcessors(): void
    {
        $messages = ['foo'];
        $this->preProcessor = $this->getMockBuilder(LoggingPreProcessor::class)
            ->setMethods(['getAndPurgeMessages'])->getMock();

        // we have to re-initialize task and demand since $this->transferTask returns wrong preProcessor
        $this->mockTransferTask();
        $this->mockTaskDemand();

        $this->preProcessor->expects($this->once())
            ->method('getAndPurgeMessages')
            ->willReturn($messages);

        self::assertInstanceOf(
            LoggingInterface::class,
            $this->preProcessor
        );
        self::assertInstanceOf(TaskResult::class,
            $this->taskResult
        );
        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->taskDemand);
    }

    public function testProcessGathersMessagesFromLoggingPostProcessors(): void
    {
        $messages = ['foo'];
        $this->postProcessor = $this->getMockBuilder(LoggingPostProcessor::class)
            ->setMethods(['getAndPurgeMessages'])->getMock();

        // we have to re-initialize task and demand since $this->transferTask returns wrong postProcessor
        $this->mockTransferTask();
        $this->mockTaskDemand();

        $this->postProcessor->expects($this->once())
            ->method('getAndPurgeMessages')
            ->willReturn($messages);

        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->taskDemand);
    }

    public function testProcessGathersMessagesFromLoggingInitializers(): void
    {
        $messages = ['foo'];
        $this->initializer = $this->getMockBuilder(LoggingInitializer::class)
            ->setMethods(['getAndPurgeMessages'])->getMock();

        // we have to re-initialize task and demand since $this->transferTask returns wrong postProcessor
        $this->mockTransferTask();
        $this->mockTaskDemand();

        $this->initializer->expects($this->once())
            ->method('getAndPurgeMessages')
            ->willReturn($messages);

        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->taskDemand);
    }

    public function testProcessGathersMessagesFromLoggingFinishers(): void
    {
        $messages = ['foo'];
        $this->finisher = $this->getMockBuilder(LoggingFinisher::class)
            ->setMethods(['getAndPurgeMessages'])->getMock();

        // we have to re-initialize task and demand since $this->transferTask returns wrong finisher
        $this->mockTransferTask();
        $this->mockTaskDemand();

        $this->finisher->expects($this->once())
            ->method('getAndPurgeMessages')
            ->willReturn($messages);

        $this->taskResult->expects($this->once())
            ->method('addMessages')
            ->with($messages);
        $this->subject->process($this->taskDemand);
    }
}
