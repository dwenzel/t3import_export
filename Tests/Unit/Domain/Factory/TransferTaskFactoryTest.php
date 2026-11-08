<?php

namespace CPSIT\T3importExport\Tests\Domain\Factory;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Factory\FactoryFactory;
use CPSIT\T3importExport\Factory\FactoryInterface;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTransferTaskTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
 * Class ImportTaskFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Domain\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Domain\Factory\TransferTaskFactory
 */
class TransferTaskFactoryTest extends TestCase
{
    use MockTransferTaskTrait,
        MockObjectManagerTrait;

    protected TransferTaskFactory $subject;

    /**
     * @var DataTargetInterface|MockObject
     */
    protected DataTargetInterface $dataTarget;

    /**
     * @var DataSourceInterface|MockObject
     */
    protected DataSourceInterface $dataSource;
    /**
     * @var DataSourceFactory|MockObject
     */
    protected DataSourceFactory $dataSourceFactory;

    /**
     * @var InitializerInterface|MockObject
     */
    protected InitializerInterface $initializer;

    /**
     * @var PreProcessorInterface|MockObject
     */
    protected PreProcessorInterface $preProcessor;
    /**
     * @var ConverterInterface|MockObject
     */
    protected ConverterInterface $converter;

    /**
     * @var PostProcessorInterface|MockObject
     */
    protected PostProcessorInterface $postProcessor;

    /**
     * @var FinisherInterface|MockObject
     */
    protected FinisherInterface $finisher;
    protected FactoryFactory $factoryFactory;
    protected FactoryInterface $factory;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp(): void
    {
        $this->factoryFactory = $this->getMockBuilder(FactoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $this->factory = $this->getMockForAbstractClass(FactoryInterface::class);

        $this->factoryFactory->method('get')
            ->willReturn($this->factory);

        $this->mockTransferTask();
        $this->mockDataTarget();
        $this->mockDataSource();
        $this->mockPreProcessor();
        $this->mockPostProcessor();
        $this->mockConverter();
        $this->mockFinisher();
        $this->mockInitializer();

        $this->subject = new TransferTaskFactory($this->factoryFactory);
    }

    protected function mockDataTarget(): void
    {
        $this->dataTarget = $this->getMockBuilder(DataTargetInterface::class)
            ->getMockForAbstractClass();
    }

    protected function mockDataSource(): void
    {
        $this->dataSource = $this->getMockForAbstractClass(DataSourceInterface::class);
    }

    protected function mockPreProcessor(): void
    {
        $this->preProcessor = $this->getMockForAbstractClass(PreProcessorInterface::class);
    }

    protected function mockPostProcessor(): void
    {
        $this->postProcessor = $this->getMockForAbstractClass(PostProcessorInterface::class);
    }

    protected function mockConverter(): void
    {
        $this->converter = $this->getMockForAbstractClass(ConverterInterface::class);
    }

    protected function mockFinisher(): void
    {
        $this->finisher = $this->getMockForAbstractClass(FinisherInterface::class);
    }

    protected function mockInitializer(): void
    {
        $this->initializer = $this->getMockForAbstractClass(InitializerInterface::class);
    }

    public function testGetSetsIdentifier(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $settings = [];
        $task = $this->subject->get($settings, $identifier);
        $this->assertSame(
            $identifier,
            $task->getIdentifier()
        );
    }

    public function testGetSetsLabel(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $label = 'bar';
        $settings = [
            'label' => $label
        ];
        $task = $this->subject->get($settings, $identifier);
        $this->assertSame(
            $label,
            $task->getLabel()
        );
    }

    public function testGetSetsTargetClass(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $targetClass = 'fooClassName';
        $settings = [
            'class' => $targetClass
        ];

        $task = $this->subject->get($settings, $identifier);
        $this->assertSame(
            $targetClass,
            $task->getTargetClass()
        );
    }

    public function testGetSetsDescription(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $description = 'fooDescription';
        $settings = [
            'description' => $description
        ];

        $task = $this->subject->get($settings, $identifier);
        $this->assertSame(
            $description,
            $task->getDescription()
        );
    }

    public function testGetSetsSourceAndTargetWithIdentifier(): void
    {
        $identifier = 'foo';
        $settings = [
            'source' => [
                'identifier' => 'sourceId'
            ],
            'target' => [
                'identifier' => 'targetId'
            ]
        ];

        $this->factoryFactory->expects($this->atLeastOnce())
            ->method('get')
            ->withConsecutive(
                [DataTargetInterface::class],
                [DataSourceInterface::class]
            )
            ->willReturn($this->factory);
        $this->factory->expects($this->atLeastOnce())
            ->method('get')
            ->withConsecutive(
                [$settings['target'], 'targetId'],
                [$settings['source'], 'sourceId'],
            )
            ->willReturnOnConsecutiveCalls(
                $this->dataTarget,
                $this->dataSource
            );
        $task = $this->subject->get($settings, $identifier);
        $this->assertSame(
            $this->dataTarget,
            $task->getTarget()
        );
        $this->assertSame(
            $this->dataSource,
            $task->getSource()
        );

    }

    public function testGetThrowsExceptionForMissingTarget(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(TransferTaskFactory::MISSING_TARGET_EXCEPTION_CODE);
        $identifier = 'foo';
        $settings = ['source' => []];

        $this->subject->get($settings, $identifier);
    }

    public function testGetThrowsExceptionForMissingSource(): void
    {
        $this->expectExceptionCode(TransferTaskFactory::MISSING_SOURCE_EXCEPTION_CODE);
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $settings = ['target' => ['foo']];

        $this->subject->get($settings, $identifier);
    }


    public function testGetSetsPreProcessors(): void
    {
        $identifier = 'bar';
        $processorClass = PreProcessorInterface::class;
        $singleConfiguration = [
            'class' => $processorClass,
            'config' => ['foo']
        ];
        $configuration = [
            'preProcessors' => [
                '1' => $singleConfiguration
            ],
            'target' => ['bar'],
            'source' => ['baz']
        ];
        $this->preProcessor->expects($this->once())
            ->method('setConfiguration')
            ->with($singleConfiguration['config']);
        $this->factoryFactory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [DataTargetInterface::class],
                [DataSourceInterface::class],
                [PreProcessorInterface::class]
            )
            ->willReturn($this->factory);
        $this->factory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [$configuration['target'], null],
                [$configuration['source'], null],
                [$singleConfiguration, $identifier])
            ->willReturnOnConsecutiveCalls(
                $this->dataTarget,
                $this->dataSource,
                $this->preProcessor
            );
        $task = $this->subject->get($configuration, $identifier);
        $processors = $task->getPreProcessors();
        $this->assertSame(
            $this->preProcessor,
            $processors['1']
        );
    }

    public function testGetSetsPostProcessors(): void
    {
        $identifier = 'bar';
        $processorClass = PostProcessorInterface::class;
        $singleConfiguration = [
            'class' => $processorClass,
            'config' => ['foo']
        ];
        $configuration = [
            'postProcessors' => [
                '1' => $singleConfiguration
            ],
            'target' => ['bar'],
            'source' => ['baz']
        ];
        $this->factoryFactory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [DataTargetInterface::class],
                [DataSourceInterface::class],
                [PostProcessorInterface::class]
            )
            ->willReturn($this->factory);
        $this->factory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [$configuration['target'], null],
                [$configuration['source'], null],
                [$singleConfiguration, $identifier])
            ->willReturnOnConsecutiveCalls(
                $this->dataTarget,
                $this->dataSource,
                $this->postProcessor
            );
        $task = $this->subject->get($configuration, $identifier);
        $processors = $task->getPostProcessors();
        $this->assertSame(
            $this->postProcessor,
            $processors['1']
        );
    }

    public function testGetSetsConverters(): void
    {
        $identifier = 'bar';
        $processorClass = ConverterInterface::class;
        $singleConfiguration = [
            'class' => $processorClass,
            'config' => ['foo']
        ];
        $configuration = [
            'converters' => [
                '1' => $singleConfiguration
            ],
            'target' => ['bar'],
            'source' => ['baz']
        ];
        $this->converter->expects($this->once())
            ->method('setConfiguration')
            ->with($singleConfiguration['config']);
        $this->factoryFactory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [DataTargetInterface::class],
                [DataSourceInterface::class],
                [ConverterInterface::class]
            )
            ->willReturn($this->factory);
        $this->factory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [$configuration['target'], null],
                [$configuration['source'], null],
                [$singleConfiguration, $identifier])
            ->willReturnOnConsecutiveCalls(
                $this->dataTarget,
                $this->dataSource,
                $this->converter
            );
        $task = $this->subject->get($configuration, $identifier);
        $processors = $task->getConverters();
        $this->assertSame(
            $this->converter,
            $processors['1']
        );
    }

    public function testGetSetsFinishers(): void
    {
        $identifier = 'bar';
        $finisherClass = FinisherInterface::class;
        $singleConfiguration = [
            'class' => $finisherClass,
            'config' => ['foo']
        ];
        $configuration = [
            'finishers' => [
                '1' => $singleConfiguration
            ],
            'target' => ['bar'],
            'source' => ['baz']
        ];

        $this->finisher->expects($this->once())
            ->method('setConfiguration')
            ->with($singleConfiguration['config']);
        $this->factoryFactory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [DataTargetInterface::class],
                [DataSourceInterface::class],
                [FinisherInterface::class]
            )
            ->willReturn($this->factory);
        $this->factory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [$configuration['target'], null],
                [$configuration['source'], null],
                [$singleConfiguration, $identifier])
            ->willReturnOnConsecutiveCalls(
                $this->dataTarget,
                $this->dataSource,
                $this->finisher
            );
        $task = $this->subject->get($configuration, $identifier);
        $processors = $task->getFinishers();
        $this->assertSame(
            $this->finisher,
            $processors['1']
        );
    }

    public function testGetSetsInitializers(): void
    {
        $identifier = 'bar';
        $initializerClass = InitializerInterface::class;
        $singleConfiguration = [
            'class' => $initializerClass,
            'config' => ['foo']
        ];
        $configuration = [
            'initializers' => [
                '1' => $singleConfiguration
            ],
            'target' => ['bar'],
            'source' => ['baz']
        ];
        $this->factoryFactory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [DataTargetInterface::class],
                [DataSourceInterface::class],
                [InitializerInterface::class]
            )
            ->willReturn($this->factory);
        $this->factory->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [$configuration['target'], null],
                [$configuration['source'], null],
                [$singleConfiguration, $identifier])
            ->willReturnOnConsecutiveCalls(
                $this->dataTarget,
                $this->dataSource,
                $this->initializer
            );
        $task = $this->subject->get($configuration, $identifier);
        $processors = $task->getInitializers();
        $this->assertSame(
            $this->initializer,
            $processors['1']
        );
    }
}
