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

use CPSIT\T3importExport\Domain\Factory\ImportSetFactory;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\ImportSet;
use CPSIT\T3importExport\Domain\Model\ImportTask;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImportSetFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Domain\Factory
 */
class ImportSetFactoryTest extends UnitTestCase
{
    /**
     * @var ImportSetFactory
     */
    protected $subject;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            ImportSetFactory::class, ['dummy']
        );
    }

    /**
     * @return mixed
     */
    protected function mockObjectManager()
    {
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $this->subject->injectObjectManager($mockObjectManager);

        return $mockObjectManager;
    }

    /**
     * @test
     */
    public function injectImportTaskFactorySetsObject()
    {
        $mockTaskFactory = $this->getMock(
            ImportTaskFactory::class
        );

        $this->subject->injectImportTaskFactory($mockTaskFactory);

        $this->assertAttributeSame(
            $mockTaskFactory,
            'importTaskFactory',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getReturnsObjectFromObjectManager()
    {
        $settings = [];
        $identifier = 'foo';

        $mockImportSet = $this->getMock(
            ImportSet::class
        );
        $mockObjectManager = $this->mockObjectManager();
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with(ImportSet::class)
            ->will($this->returnValue($mockImportSet));

        $this->assertSame(
            $mockImportSet,
            $this->subject->get($settings)
        );
    }

    /**
     * @test
     */
    public function getSetsIdentifier()
    {
        $settings = [];
        $identifier = 'foo';

        $mockImportSet = $this->getMock(
            ImportSet::class, ['setIdentifier']
        );
        $mockObjectManager = $this->mockObjectManager();
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockImportSet));

        $mockImportSet->expects($this->once())
            ->method('setIdentifier')
            ->with($identifier);

        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     */
    public function getSetsDescription()
    {
        $description = 'foo';
        $settings = [
            'description' => $description
        ];

        $mockImportSet = $this->getMock(
            ImportSet::class, ['setDescription']
        );
        $mockObjectManager = $this->mockObjectManager();
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockImportSet));

        $mockImportSet->expects($this->once())
            ->method('setDescription')
            ->with($description);

        $this->subject->get($settings);
    }

    /**
     * @test
     */
    public function getSetsLabel()
    {
        $label = 'foo';
        $settings = [
            'label' => $label
        ];

        $mockImportSet = $this->getMock(
            ImportSet::class, ['setLabel']
        );
        $mockObjectManager = $this->mockObjectManager();
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockImportSet));

        $mockImportSet->expects($this->once())
            ->method('setLabel')
            ->with($label);

        $this->subject->get($settings);
    }

    /**
     * @test
     */
    public function getSetsTask()
    {
        $fooTaskConfiguration = ['baz'];
        $barTaskConfiguration = ['bam'];
        $frameworkSettings = [
            'importProcessor' => [
                'tasks' => [
                    'foo' => $fooTaskConfiguration,
                    'bar' => $barTaskConfiguration
                ]
            ]
        ];
        $this->subject->_set(
            'settings', $frameworkSettings
        );
        $config = [
            'tasks' => 'foo,bar,,'
        ];
        $mockImportTaskFactory = $this->getMock(
            ImportTaskFactory::class, ['get']
        );

        $mockImportSet = $this->getMock(
            ImportSet::class, ['setTasks']
        );
        $mockObjectManager = $this->mockObjectManager();
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockImportSet));
        $mockImportTask = $this->getMock(
            ImportTask::class
        );
        $mockImportTaskFactory->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$fooTaskConfiguration, 'foo'],
                [$barTaskConfiguration, 'bar']
            )
            ->will($this->returnValue($mockImportTask));
        $this->subject->injectImportTaskFactory($mockImportTaskFactory);

        $expectedTasks = [
            'foo' => $mockImportTask,
            'bar' => $mockImportTask
        ];
        $mockImportSet->expects($this->once())
            ->method('setTasks')
            ->with($expectedTasks);

        $this->subject->get($config);
    }
}
