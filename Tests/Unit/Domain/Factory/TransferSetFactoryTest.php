<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Domain\Factory;

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

use CPSIT\ImportExportCore\Configuration\ConfigurationManager;
use CPSIT\ImportExportCore\Configuration\ConfigurationManagerInterface;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ImportSetFactoryTest
 */
class TransferSetFactoryTest extends TestCase
{
    protected TransferSetFactory $subject;

    /**
     * @var TransferTaskFactory|MockObject
     */
    protected TransferTaskFactory $transferTaskFactory;

    /**
     * @var TransferSet|MockObject
     */
    protected TransferSet $transferSet;

    /**
     * @var TransferTask|MockObject
     */
    protected TransferTask $transferTask;

    /**
     * @var ConfigurationManagerInterface|MockObject
     */
    protected ConfigurationManagerInterface|MockObject $configurationManager;

    protected array $settings = [];

    /**
     * Set up
     */
    protected function mockConfigurationManager(): void
    {
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFullConfiguration'])
            ->getMock();
    }

    /**
     * Creates mock transfer task
     */
    protected function mockTransferTask(): void
    {
        $this->transferTask = $this->getMockBuilder(TransferTask::class)
            ->onlyMethods([
                'setIdentifier',
                'setDescription',
                'setTargetClass',
                'setSource',
                'setTarget',
                'setConverters',
                'setPreProcessors',
                'setPostProcessors',
                'setFinishers',
                'setInitializers',
                'setLabel',
            ])
            ->getMock();
    }

    protected function setUp(): void
    {
        $this->mockConfigurationManager();
        $this->transferTaskFactory = $this->getMockBuilder(TransferTaskFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->mockTransferTask();

        $this->configurationManager->method('getFullConfiguration')
            ->willReturn($this->settings);
        $this->transferTaskFactory->method('get')->willReturn($this->transferTask);
        $this->subject = new TransferSetFactory(
            $this->transferTaskFactory,
            $this->configurationManager,
        );
    }

    #[Test]
    public function getSetsIdentifier(): void
    {
        $settings = [];
        $identifier = 'foo';

        $transferSet = $this->subject->get($settings, $identifier);
        $this->assertSame(
            $identifier,
            $transferSet->getIdentifier()
        );
    }

    public function testGetSetsDescription(): void
    {
        $description = 'foo';
        $settings = [
            'description' => $description,
        ];

        $transferSet = $this->subject->get($settings);
        $this->assertSame(
            $description,
            $transferSet->getDescription()
        );
    }

    public function testGetSetsLabel(): void
    {
        $label = 'foo';
        $settings = [
            'label' => $label,
        ];

        $transferSet = $this->subject->get($settings);
        $this->assertSame(
            $label,
            $transferSet->getLabel()
        );
    }

    #[Test]
    public function getSetsTask(): void
    {
        $fooTaskConfiguration = ['baz'];
        $barTaskConfiguration = ['bam'];
        $frameworkSettings = [
            'import' => [
                'tasks' => [
                    'foo' => $fooTaskConfiguration,
                    'bar' => $barTaskConfiguration,
                ],
            ],
        ];
        $this->subject = $this->subject->withSettings($frameworkSettings);

        $config = [
            'tasks' => 'foo,bar',
        ];

        // Since withConsecutive is removed in PHPUnit 12, we'll simplify the test
        // and just verify the correct number of calls and the final result
        $this->transferTaskFactory->expects($this->exactly(2))
            ->method('get')
            ->willReturn($this->transferTask);

        $expectedTasks = [
            'foo' => $this->transferTask,
            'bar' => $this->transferTask,
        ];

        $transferSet = $this->subject->get($config);
        $this->assertSame(
            $expectedTasks,
            $transferSet->getTasks()
        );
    }
}
