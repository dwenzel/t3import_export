<?php

namespace CPSIT\T3importExport\Tests\Domain\Factory;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Tests\Unit\Traits\MockConfigurationManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTransferTaskTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ImportSetFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Domain\Factory
 */
class TransferSetFactoryTest extends TestCase
{
    use MockObjectManagerTrait,
        MockConfigurationManagerTrait,
        MockTransferTaskTrait;

    protected TransferSetFactory $subject;

    /**
     * @var TransferTaskFactory|MockObject
     */
    protected TransferTaskFactory $transferTaskFactory;

    /**
     * @var TransferSet|MockObject
     */
    protected TransferSet $transferSet;

    protected array $settings = [];
    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->mockConfigurationManager();
        $this->transferTaskFactory = $this->getMockBuilder(TransferTaskFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $this->transferSet = $this->getMockBuilder(TransferSet::class)
            ->setMethods(
                [
                    'setIdentifier',
                    'setDescription',
                    'setLabel',
                    'setTasks'
                ])
            ->getMock();

        $this->mockTransferTask();

        $this->configurationManager->method('getConfiguration')
            ->willReturn($this->settings);
        $this->transferTaskFactory->method('get')->willReturn($this->transferTask);
        $this->subject = new TransferSetFactory(
            $this->transferTaskFactory,
            $this->configurationManager,
            $this->transferSet
        );
    }

    public function testGetSetsIdentifier(): void
    {
        $settings = [];
        $identifier = 'foo';

        $this->transferSet->expects($this->once())
            ->method('setIdentifier')
            ->with(...[$identifier]);

        $this->subject->get($settings, $identifier);
    }

    public function testGetSetsDescription(): void
    {
        $description = 'foo';
        $settings = [
            'description' => $description
        ];

        $this->transferSet->expects($this->once())
            ->method('setDescription')
            ->with(...[$description]);

        $this->subject->get($settings);
    }

    public function testGetSetsLabel(): void
    {
        $label = 'foo';
        $settings = [
            'label' => $label
        ];

        $this->transferSet->expects($this->once())
            ->method('setLabel')
            ->with(...[$label]);

        $this->subject->get($settings);
    }

    /**
     * @test
     */
    public function getSetsTask(): void
    {
        $fooTaskConfiguration = ['baz'];
        $barTaskConfiguration = ['bam'];
        $frameworkSettings = [
            'import' => [
                'tasks' => [
                    'foo' => $fooTaskConfiguration,
                    'bar' => $barTaskConfiguration
                ]
            ]
        ];
        $this->subject = $this->subject->withSettings($frameworkSettings);

        $config = [
            'tasks' => 'foo,bar'
        ];

        $this->transferTaskFactory->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$fooTaskConfiguration, 'foo'],
                [$barTaskConfiguration, 'bar']
            )
            ->willReturn($this->transferTask);

        $expectedTasks = [
            'foo' => $this->transferTask,
            'bar' => $this->transferTask
        ];
        $this->transferSet->expects($this->once())
            ->method('setTasks')
            ->with($expectedTasks);

        $this->subject->get($config);
    }

}
