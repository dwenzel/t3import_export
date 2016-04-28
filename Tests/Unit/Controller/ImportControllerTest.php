<?php
namespace CPSIT\T3importExport\Tests\Controller;

use CPSIT\T3importExport\Domain\Factory\ImportSetFactory;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\ImportSet;
use CPSIT\T3importExport\Domain\Model\ImportTask;
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
 * Class ImportControllerTest
 *
 * @package CPSIT\T3importExport\Tests\Controller
 * @coversDefaultClass \CPSIT\T3importExport\Controller\ImportController
 */
class ImportControllerTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Controller\ImportController
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock('CPSIT\\T3importExport\\Controller\\ImportController',
			['dummy'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::injectImportProcessor
	 */
	public function injectImportProcessorForObjectSetsImportProcessor() {
		$expectedProcessor = $this->getMock('CPSIT\\T3importExport\\Service\\ImportProcessor');
		$this->subject->injectImportProcessor($expectedProcessor);

		$this->assertSame(
			$expectedProcessor,
			$this->subject->_get('importProcessor')
		);
	}

	/**
	 * @test
	 */
	public function injectImportTaskFactorySetsFactory() {
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
	public function injectImportSetFactorySetsFactory() {
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
	 * @covers ::importTaskAction
	 */
	public function importTaskActionBuildsAndProcessQueueAndAssignsVariables() {
		$identifier = 'foo';
		$settings = [
			'importProcessor' => [
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
			->with($settings['importProcessor']['tasks'][$identifier])
			->will($this->returnValue($mockTask));
		$this->subject->injectImportTaskFactory($importTaskFactory);

		$importProcessor = $this->getMock(
			'CPSIT\\T3importExport\\Service\\ImportProcessor',
			['buildQueue', 'process'], [], '', FALSE
		);
		$task = 'foo';
		$result = ['bar'];
		$this->subject->injectImportProcessor($importProcessor);
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
		$this->subject->importTaskAction($task);
	}

	/**
	 * @test
	 */
	public function indexActionBuildsTasks() {
		$identifierForTask = 'foo';
		$settingsForTask = ['fooTaskSettings'];
		$settings = [
			'importProcessor' => [
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
			'importProcessor' => [
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
	 * @covers ::importSetAction
	 */
	public function importSetActionBuildsAndProcessQueueAndAssignsVariables() {
		$identifier = 'foo';
		$settings = [
			'importProcessor' => [
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
			->with($settings['importProcessor']['sets'][$identifier])
			->will($this->returnValue($mockSet));
		$this->subject->injectImportSetFactory($importSetFactory);

		$importProcessor = $this->getMock(
			'CPSIT\\T3importExport\\Service\\ImportProcessor',
			['buildQueue', 'process'], [], '', FALSE
		);
		$set = 'foo';
		$result = ['bar'];
		$this->subject->injectImportProcessor($importProcessor);
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
		$this->subject->importSetAction($set);
	}
}
