<?php
namespace CPSIT\T3import\Tests\Controller;

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
 * @package CPSIT\T3import\Tests\Controller
 * @coversDefaultClass \CPSIT\T3import\Controller\ImportController
 */
class ImportControllerTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Controller\ImportController
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock('CPSIT\\T3import\\Controller\\ImportController',
			['dummy'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::injectImportProcessor
	 */
	public function injectImportProcessorForObjectSetsImportProcessor() {
		$expectedProcessor = $this->getMock('CPSIT\\T3import\\Service\\ImportProcessor');
		$this->subject->injectImportProcessor($expectedProcessor);

		$this->assertSame(
			$expectedProcessor,
			$this->subject->_get('importProcessor')
		);
	}

	/**
	 * @test
	 * @covers ::importTaskAction
	 */
	public function importTaskActionBuildsAndProcessQueueAndAssignsVariables() {
		$importProcessor = $this->getMock(
			'CPSIT\\T3import\\Service\\ImportProcessor',
			['buildQueue', 'process'], [], '', FALSE
		);
		$task = 'foo';
		$result = ['bar'];
		$this->subject->injectImportProcessor($importProcessor);
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get']);
		$this->subject->injectObjectManager($mockObjectManager);
		$mockDemand = $this->getMock(
			'CPSIT\\T3import\\Domain\\Model\\Dto\\DemandInterface'
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
}
