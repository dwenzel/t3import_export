<?php

namespace CPSIT\T3importExport\Tests\Domain\Factory;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Factory\ConverterFactory;
use CPSIT\T3importExport\Component\Factory\FinisherFactory;
use CPSIT\T3importExport\Component\Factory\InitializerFactory;
use CPSIT\T3importExport\Component\Factory\PostProcessorFactory;
use CPSIT\T3importExport\Component\Factory\PreProcessorFactory;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;
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
     * @var DataTargetFactory|MockObject
     */
    protected DataTargetFactory $dataTargetFactory;

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
     * @var InitializerFactory|MockObject
     */
    protected InitializerFactory $initializerFactory;

    /**
     * @var PreProcessorInterface|MockObject
     */
    protected PreProcessorInterface $preProcessor;
    /**
     * @var PreProcessorFactory|MockObject
     */
    protected PreProcessorFactory $preProcessorFactory;

    /**
     * @var ConverterInterface|MockObject
     */
    protected ConverterInterface $converter;

    /**
     * @var ConverterFactory|MockObject
     */
    protected ConverterFactory $converterFactory;

    /**
     * @var PostProcessorInterface|MockObject
     */
    protected PostProcessorInterface $postProcessor;

    /**
     * @var PostProcessorFactory|MockObject
     */
    protected PostProcessorFactory $postProcessorFactory;

    /**
     * @var FinisherInterface|MockObject
     */
    protected FinisherInterface $finisher;

    /**
     * @var FinisherFactory|MockObject
     */
    protected FinisherFactory $finisherFactory;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp()
    {
        $this->subject = new TransferTaskFactory();
        $this->mockObjectManager();
        $this->mockTransferTask();
        $this->mockDataTarget();
        $this->mockDataTargetFactory();
        $this->mockDataSource();
        $this->mockDataSourceFactory();
        $this->mockPreProcessor();
        $this->mockPreProcessorFactory();
        $this->mockPostProcessor();
        $this->mockPostProcessorFactory();
        $this->mockConverter();
        $this->mockConverterFactory();
        $this->mockFinisher();
        $this->mockFinisherFactory();
        $this->mockInitializer();
        $this->mockInitializerFactory();

        $this->objectManager->method('get')->willReturn($this->transferTask);
    }

    protected function mockDataTarget(): void
    {
        $this->dataTarget = $this->getMockBuilder(DataTargetInterface::class)
            ->getMockForAbstractClass();
    }

    protected function mockDataTargetFactory(): void
    {
        $this->dataTargetFactory = $this->getMockBuilder(DataTargetFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->dataTargetFactory->method('get')->willReturn($this->dataTarget);
        $this->subject->injectDataTargetFactory($this->dataTargetFactory);
    }

    protected function mockDataSource(): void
    {
        $this->dataSource = $this->getMockForAbstractClass(DataSourceInterface::class);
    }

    protected function mockDataSourceFactory(): void
    {
        $this->dataSourceFactory = $this->getMockBuilder(DataSourceFactory::class)
            ->setMethods([])
            ->getMock();
        $this->dataSourceFactory->method('get')->willReturn($this->dataSource);
        $this->subject->injectDataSourceFactory($this->dataSourceFactory);
    }

    protected function mockPreProcessor(): void
    {
        $this->preProcessor = $this->getMockForAbstractClass(PreProcessorInterface::class);
    }

    protected function mockPreProcessorFactory(): void
    {
        $this->preProcessorFactory = $this->getMockBuilder(PreProcessorFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->preProcessorFactory->method('get')->willReturn($this->preProcessor);
        $this->subject->injectPreProcessorFactory($this->preProcessorFactory);
    }

    protected function mockPostProcessor(): void
    {
        $this->postProcessor = $this->getMockForAbstractClass(PostProcessorInterface::class);
    }

    protected function mockPostProcessorFactory(): void
    {
        $this->postProcessorFactory = $this->getMockBuilder(PostProcessorFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->postProcessorFactory->method('get')->willReturn($this->postProcessor);
        $this->subject->injectPostProcessorFactory($this->postProcessorFactory);
    }

    protected function mockConverter(): void
    {
        $this->converter = $this->getMockForAbstractClass(ConverterInterface::class);
    }

    protected function mockConverterFactory(): void
    {
        $this->converterFactory = $this->getMockBuilder(ConverterFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->converterFactory->method('get')->willReturn($this->converter);
        $this->subject->injectConverterFactory($this->converterFactory);
    }

    protected function mockFinisher(): void
    {
        $this->finisher = $this->getMockForAbstractClass(FinisherInterface::class);
    }

    protected function mockFinisherFactory(): void
    {
        $this->finisherFactory = $this->getMockBuilder(FinisherFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->finisherFactory->method('get')->willReturn($this->finisher);
        $this->subject->injectFinisherFactory($this->finisherFactory);
    }

    protected function mockInitializer(): void
    {
        $this->initializer = $this->getMockForAbstractClass(InitializerInterface::class);
    }

    protected function mockInitializerFactory(): void
    {
        $this->initializerFactory = $this->getMockBuilder(InitializerFactory::class)
            ->setMethods(['get'])
            ->getMock();
        $this->initializerFactory->method('get')->willReturn($this->initializer);
        $this->subject->injectInitializerFactory($this->initializerFactory);
    }

    public function testInjectDataSourceFactorySetsFactory(): void
    {
        $mockFactory = $this->getMockBuilder(DataSourceFactory::class)
            ->getMock();
        $this->subject->injectDataSourceFactory(
            $mockFactory
        );
        $this->assertAttributeEquals(
            $mockFactory,
            'dataSourceFactory',
            $this->subject
        );
    }

    public function testInjectPreProcessorFactorySetsFactory(): void
    {
        /** @var PreProcessorFactory|MockObject $mockFactory */
        $mockFactory = $this->getMockBuilder(PreProcessorFactory::class)
            ->getMock();
        $this->subject->injectPreProcessorFactory($mockFactory);
        $this->assertAttributeEquals(
            $mockFactory,
            'preProcessorFactory',
            $this->subject
        );
    }

    public function testInjectPostProcessorFactorySetsFactory(): void
    {
        $mockFactory = $this->getMockBuilder(PostProcessorFactory::class)
            ->getMock();
        $this->subject->injectPostProcessorFactory(
            $mockFactory
        );
        $this->assertAttributeEquals(
            $mockFactory,
            'postProcessorFactory',
            $this->subject
        );
    }

    public function testInjectConverterFactorySetsFactory(): void
    {
        $mockFactory = $this->getMockBuilder(ConverterFactory::class)
            ->getMock();
        $this->subject->injectConverterFactory($mockFactory);
        $this->assertAttributeEquals(
            $mockFactory,
            'converterFactory',
            $this->subject
        );
    }

    public function testInjectFinisherFactorySetsFactory(): void
    {
        $mockFactory = $this->getMockBuilder(FinisherFactory::class)
            ->getMock();
        $this->subject->injectFinisherFactory($mockFactory);
        $this->assertAttributeEquals(
            $mockFactory,
            'finisherFactory',
            $this->subject
        );
    }

    public function testInjectInitializerFactorySetsFactory(): void
    {
        $mockFactory = $this->getMockBuilder(InitializerFactory::class)
            ->getMock();
        $this->subject->injectInitializerFactory(
            $mockFactory
        );
        $this->assertAttributeEquals(
            $mockFactory,
            'initializerFactory',
            $this->subject
        );
    }

    public function testInjectDataTargetFactorySetsFactory(): void
    {
        $mockFactory = $this->getMockBuilder(DataTargetFactory::class)
            ->getMock();
        $this->subject->injectDataTargetFactory($mockFactory);
        $this->assertAttributeEquals(
            $mockFactory,
            'dataTargetFactory',
            $this->subject
        );
    }

    public function testGetGetsImportTaskFromObjectManager(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $settings = [];
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(...[TransferTask::class])
            ->willReturn($this->transferTask);

        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsIdentifier(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $settings = [];
        $this->transferTask->expects($this->once())
            ->method('setIdentifier')
            ->with(...[$identifier]);
        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsLabel(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $label = 'bar';
        $settings = [
            'label' => $label
        ];
        $this->transferTask->expects($this->once())
            ->method('setLabel')
            ->with(...[$label]);
        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsTargetClass(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $targetClass = 'fooClassName';
        $settings = [
            'class' => $targetClass
        ];

        $this->transferTask->expects($this->once())
            ->method('setTargetClass')
            ->with($targetClass);

        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsDescription(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $identifier = 'foo';
        $description = 'fooDescription';
        $settings = [
            'description' => $description
        ];

        $this->transferTask->expects($this->once())
            ->method('setDescription')
            ->with(...[$description]);

        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsTarget(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $identifier = 'foo';
        $settings = [
            'target' => [
                'identifier' => 'bar'
            ]
        ];

        $this->transferTask->expects($this->once())
            ->method('setTarget')
            ->with(...[$this->dataTarget]);

        $this->subject->get($settings, $identifier);
    }

    public function testGetThrowsExceptionForMissingTarget(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451052262);
        $identifier = 'foo';
        $settings = ['foo'];

        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451206701
     */
    public function getThrowsExceptionForMissingSource(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451206701);
        $identifier = 'foo';
        $settings = ['target' => ['foo']];

        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsSource(): void
    {
        $identifier = 'foo';
        $settings = [
            'source' => [
                'identifier' => 'bar'
            ],
            'target' => [
                'identifier' => 'baz'
            ]
        ];

        $this->dataSourceFactory->expects($this->once())
            ->method('get')
            ->with($settings['source']);
        $this->transferTask->expects($this->once())
            ->method('setSource')
            ->with(...[$this->dataSource]);

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
        $this->preProcessorFactory->expects($this->once())
            ->method('get')
            ->with(...[$singleConfiguration, $identifier]);
        $this->transferTask->expects($this->once())
            ->method('setPreProcessors')
            ->with(['1' => $this->preProcessor]);
        $this->subject->get($configuration, $identifier);
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
        $this->postProcessor->expects($this->once())
            ->method('setConfiguration')
            ->with($singleConfiguration['config']);
        $this->postProcessorFactory->expects($this->once())
            ->method('get')
            ->with(...[$singleConfiguration, $identifier]);
        $this->transferTask->expects($this->once())
            ->method('setPostProcessors')
            ->with(['1' => $this->postProcessor]);
        $this->subject->get($configuration, $identifier);
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
        $this->converterFactory->expects($this->once())
            ->method('get')
            ->with(...[$singleConfiguration, $identifier]);
        $this->transferTask->expects($this->once())
            ->method('setConverters')
            ->with(['1' => $this->converter]);
        $this->subject->get($configuration, $identifier);
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
        $this->finisherFactory->expects($this->once())
            ->method('get')
            ->with(...[$singleConfiguration, $identifier]);
        $this->transferTask->expects($this->once())
            ->method('setFinishers')
            ->with(['1' => $this->finisher]);
        $this->subject->get($configuration, $identifier);
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
        $this->initializer->expects($this->once())
            ->method('setConfiguration')
            ->with($singleConfiguration['config']);
        $this->initializerFactory->expects($this->once())
            ->method('get')
            ->with(...[$singleConfiguration, $identifier]);
        $this->transferTask->expects($this->once())
            ->method('setInitializers')
            ->with(['1' => $this->initializer]);
        $this->subject->get($configuration, $identifier);
    }
}
