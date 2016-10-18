<?php
namespace CPSIT\T3importExport\Tests\Controller;

use CPSIT\T3importExport\Controller\ExportController;
use CPSIT\T3importExport\Domain\Factory\ImportSetFactory;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\ImportSet;
use CPSIT\T3importExport\Domain\Model\ImportTask;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
class ExportControllerTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Controller\ExportController
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(ExportController::class,
			['dummy'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::injectDataTransferProcessor
	 */
	public function injectDataTransferProcessorForObjectSetsDataTransferProcessor() {
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
	public function injectExportTaskFactorySetsFactory() {
		$factory = $this->getMock(
			ImportTaskFactory::class
		);
		$this->subject->injectImportTaskFactory($factory);
		$this->assertSame(
			$factory,
			$this->subject->_get('importTaskFactory')
		);
	}

	/**
	 * @test
	 */
	public function injectExportSetFactorySetsFactory() {
		$factory = $this->getMock(
			ImportSetFactory::class
		);
		$this->subject->injectImportSetFactory($factory);
		$this->assertSame(
			$factory,
			$this->subject->_get('importSetFactory')
		);
	}

	/**
	 * @test
	 */
	public function exportTaskActionBuildsAndProcessQueueAndAssignsVariables() {
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
			ImportTask::class
		);
		$importTaskFactory = $this->getMock(
			ImportTaskFactory::class, ['get']
		);
		$importTaskFactory->expects($this->once())
			->method('get')
			->with($settings['export']['tasks'][$identifier])
			->will($this->returnValue($mockTask));
		$this->subject->injectImportTaskFactory($importTaskFactory);

		$importProcessor = $this->getMock(
			DataTransferProcessor::class,
			['buildQueue', 'process'], [], '', FALSE
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
			TemplateView::class, ['assignMultiple'], [], '', FALSE
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
	public function indexActionBuildsTasks() {
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
			ImportTaskFactory::class, ['get']
		);
		$this->subject->injectImportTaskFactory($mockTaskFactory);

		$mockTaskFactory->expects($this->once())
			->method('get')
			->with($settingsForTask, $identifierForTask);

		$this->subject->indexAction();
	}

	/**
	 * @test
	 */
	public function indexActionBuildsSets() {
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
			ImportSetFactory::class, ['get']
		);
		$this->subject->injectImportSetFactory($mockSetFactory);

		$mockSetFactory->expects($this->once())
			->method('get')
			->with($settingsForSet, $identifierForSet);

		$this->subject->indexAction();
	}


	/**
	 * @test
	 */
	public function exportSetActionBuildsAndProcessQueueAndAssignsVariables() {
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
			ImportSet::class, ['getTasks']
		);
		$mockSet->expects($this->once())
			->method('getTasks')
			->will($this->returnValue([]));
		$importSetFactory = $this->getMock(
			ImportSetFactory::class, ['get']
		);
		$importSetFactory->expects($this->once())
			->method('get')
			->with($settings['export']['sets'][$identifier])
			->will($this->returnValue($mockSet));
		$this->subject->injectImportSetFactory($importSetFactory);

		$importProcessor = $this->getMock(
			DataTransferProcessor::class,
			['buildQueue', 'process'], [], '', FALSE
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
			TemplateView::class, ['assignMultiple'], [], '', FALSE
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
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 123476532
     */
	public function exportSetActionThrowsErrorForMissingSettingsKey()
    {
        $invalidSettings = [];
        $this->inject(
            $this->subject,
            'settings',
            $invalidSettings
        );
        $this->subject->exportSetAction('foo');
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 123476532
     */
    public function indexActionThrowsErrorForMissingSettingsKey()
    {
        $invalidSettings = [];
        $this->inject(
            $this->subject,
            'settings',
            $invalidSettings
        );
        $this->subject->indexAction('foo');
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 123476532
     */
    public function exportTaskActionThrowsErrorForMissingSettingsKey()
    {
        $invalidSettings = [];
        $this->inject(
            $this->subject,
            'settings',
            $invalidSettings
        );
        $this->subject->exportTaskAction('foo');
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
