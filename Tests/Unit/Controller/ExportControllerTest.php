<?php

namespace CPSIT\T3importExport\Tests\Controller;

use CPSIT\T3importExport\Controller\ExportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\TemplateView;

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
 * Class ExportControllerTest
 *
 * @package CPSIT\T3importExport\Tests\Controller
 * @coversDefaultClass \CPSIT\T3importExport\Controller\ExportController
 */
class ExportControllerTest extends TestCase
{

    /**
     * @var \CPSIT\T3importExport\Controller\ExportController
     */
    protected $subject;

    public function setUp()
    {
        $this->markTestSkipped('Todo: replace ExtbaseCommandController by Symfony Command');

        $this->subject = $this->getAccessibleMock(ExportController::class,
            ['dummy'], [], '', false);
    }

    /**
     * @test
     * @covers ::injectDataTransferProcessor
     */
    public function injectDataTransferProcessorForObjectSetsDataTransferProcessor()
    {
        $expectedProcessor = $this->getMock(DataTransferProcessor::class);
        $this->subject->injectDataTransferProcessor($expectedProcessor);

        $this->assertSame(
            $expectedProcessor,
            $this->subject->_get('dataTransferProcessor')
        );
    }

    /**
     * @test
     */
    public function injectExportTaskFactorySetsFactory()
    {
        $factory = $this->getMock(
            TransferTaskFactory::class
        );
        $this->subject->injectTransferTaskFactory($factory);
        $this->assertSame(
            $factory,
            $this->subject->_get('transferTaskFactory')
        );
    }

    /**
     * @test
     */
    public function injectExportSetFactorySetsFactory()
    {
        $factory = $this->getMock(
            TransferSetFactory::class
        );
        $this->subject->injectTransferSetFactory($factory);
        $this->assertSame(
            $factory,
            $this->subject->_get('transferSetFactory')
        );
    }

    /**
     * @test
     */
    public function exportTaskActionBuildsAndProcessQueueAndAssignsVariables()
    {
        $identifier = 'foo';
        $settings = [
            'export' => [
                'tasks' => [
                    $identifier => ['bar']
                ]
            ]
        ];
        $this->subject->_set('settings', $settings);
        $mockTask = $this->getMock(
            TransferTask::class
        );
        $transferTaskFactory = $this->getMock(
            TransferTaskFactory::class, ['get']
        );
        $transferTaskFactory->expects($this->once())
            ->method('get')
            ->with($settings['export']['tasks'][$identifier])
            ->will($this->returnValue($mockTask));
        $this->subject->injectTransferTaskFactory($transferTaskFactory);

        $importProcessor = $this->getMock(
            DataTransferProcessor::class,
            ['buildQueue', 'process'], [], '', false
        );
        $task = 'foo';
        $result = ['bar'];
        $this->subject->injectDataTransferProcessor($importProcessor);
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockDemand = $this->getMock(
            'CPSIT\\T3importExport\\Domain\\Model\\Dto\\DemandInterface'
        );
        $mockView = $this->getMock(
            TemplateView::class, ['assignMultiple'], [], '', false
        );
        $this->subject->_set('view', $mockView);

        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockDemand));
        $importProcessor->expects($this->once())
            ->method('buildQueue')
            ->with($mockDemand);
        $importProcessor->expects($this->once())
            ->method('process')
            ->with($mockDemand)
            ->will($this->returnValue($result));
        $mockView->expects($this->once())
            ->method('assignMultiple')
            ->with(
                [
                    'task' => $task,
                    'result' => $result
                ]
            );
        $this->subject->exportTaskAction($task);
    }

    /**
     * @test
     */
    public function indexActionBuildsTasks()
    {
        $identifierForTask = 'foo';
        $settingsForTask = ['fooTaskSettings'];
        $settings = [
            'export' => [
                'tasks' => [
                    $identifierForTask => $settingsForTask
                ]
            ]
        ];
        $this->subject->_set('settings', $settings);
        $mockView = $this->getMockForAbstractClass(
            ViewInterface::class
        );

        $this->subject->_set('view', $mockView);
        $mockTaskFactory = $this->getMock(
            TransferTaskFactory::class, ['get']
        );
        $this->subject->injectTransferTaskFactory($mockTaskFactory);

        $mockTaskFactory->expects($this->once())
            ->method('get')
            ->with($settingsForTask, $identifierForTask);

        $this->subject->indexAction();
    }

    /**
     * @test
     */
    public function indexActionBuildsSets()
    {
        $identifierForSet = 'foo';
        $settingsForSet = ['fooSetSettings'];
        $settings = [
            'export' => [
                'sets' => [
                    $identifierForSet => $settingsForSet
                ]
            ]
        ];
        $this->subject->_set('settings', $settings);
        $mockView = $this->getMockForAbstractClass(
            ViewInterface::class
        );

        $this->subject->_set('view', $mockView);
        $mockSetFactory = $this->getMock(
            TransferSetFactory::class, ['get']
        );
        $this->subject->injectTransferSetFactory($mockSetFactory);

        $mockSetFactory->expects($this->once())
            ->method('get')
            ->with($settingsForSet, $identifierForSet);

        $this->subject->indexAction();
    }


    /**
     * @test
     */
    public function exportSetActionBuildsAndProcessQueueAndAssignsVariables()
    {
        $identifier = 'foo';
        $settings = [
            'export' => [
                'sets' => [
                    $identifier => ['bar']
                ]
            ]
        ];
        $this->subject->_set('settings', $settings);
        $mockSet = $this->getMock(
            TransferSet::class, ['getTasks']
        );
        $mockSet->expects($this->once())
            ->method('getTasks')
            ->will($this->returnValue([]));
        $importSetFactory = $this->getMock(
            TransferSetFactory::class, ['get']
        );
        $importSetFactory->expects($this->once())
            ->method('get')
            ->with($settings['export']['sets'][$identifier])
            ->will($this->returnValue($mockSet));
        $this->subject->injectTransferSetFactory($importSetFactory);

        $importProcessor = $this->getMock(
            DataTransferProcessor::class,
            ['buildQueue', 'process'], [], '', false
        );
        $set = 'foo';
        $result = ['bar'];
        $this->subject->injectDataTransferProcessor($importProcessor);
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockDemand = $this->getMock(
            'CPSIT\\T3importExport\\Domain\\Model\\Dto\\DemandInterface'
        );
        $mockView = $this->getMock(
            TemplateView::class, ['assignMultiple'], [], '', false
        );
        $this->subject->_set('view', $mockView);

        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockDemand));
        $importProcessor->expects($this->once())
            ->method('buildQueue')
            ->with($mockDemand);
        $importProcessor->expects($this->once())
            ->method('process')
            ->with($mockDemand)
            ->will($this->returnValue($result));
        $mockView->expects($this->once())
            ->method('assignMultiple')
            ->with(
                [
                    'set' => $set,
                    'result' => $result
                ]
            );
        $this->subject->exportSetAction($set);
    }

    /**
     * @test
     */
    public function getSettingsKeyReturnsClassConstant()
    {
        $this->assertSame(
            ExportController::SETTINGS_KEY,
            $this->subject->getSettingsKey()
        );
    }
}
