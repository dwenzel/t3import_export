<?php
namespace CPSIT\T3import\Tests\Command;

use CPSIT\T3import\Command\ImportCommandController;
use CPSIT\T3import\Service\ImportProcessor;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
 * Class DummyConnectionService
 * replaces external dependency
 *
 * @package CPSIT\T3import\Tests\Service
 */
class DummyConnectionService {
}

/**
 * Class DummyRepository
 * replaces external dependency
 *
 * @package CPSIT\T3import\Tests\Service
 */
class DummyRepository {
}

/**
 * Class ImportCommandControllerTest
 *
 * @package CPSIT\T3import\Tests\Command
 * @coversDefaultClass \CPSIT\T3import\Command\ImportCommandController
 */
class ImportCommandControllerTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Command\ImportCommandController
	 */
	protected $subject;

	public function setUp() {
		if (!class_exists('CPSIT\\ZewProjectconf\\Service\\ZewDbConnectionService')) {
			class_alias(
				'CPSIT\\T3import\\Tests\\Command\\DummyConnectionService',
				'CPSIT\\ZewProjectconf\\Service\\ZewDbConnectionService'
			);
		}
		if (!class_exists('CPSIT\\ZewEvents\\Domain\\Repository\\EventRepository')) {
			class_alias(
				'CPSIT\\T3import\\Tests\\Command\\DummyRepository',
				'CPSIT\\ZewEvents\\Domain\\Repository\\EventRepository'
			);
		}
		if (!class_exists('CPSIT\\ZewEvents\\Domain\\Repository\\PerformanceRepository')) {
			class_alias(
				'CPSIT\\T3import\\Tests\\Command\\DummyRepository',
				'CPSIT\\ZewEvents\\Domain\\Repository\\PerformanceRepository'
			);
		}
		$this->subject = $this->getAccessibleMock('CPSIT\\T3import\\Command\\ImportCommandController',
			array('dummy'), array(), '', FALSE);
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
	 * @covers ::importCommand
	 */
	public function importCommandBuildsQueue() {
		$eventImportProcessor = $this->getMock(
			'CPSIT\\T3import\\Service\\ImportProcessor',
			array('buildQueue')
		);
		$this->subject->injectImportProcessor($eventImportProcessor);
		$mockDemand = $this->getMock(
			'CPSIT\\T3import\\Domain\\Model\\Dto\\DemandInterface'
		);

		$eventImportProcessor->expects($this->once())
			->method('buildQueue')
			->with($mockDemand);
		$this->subject->importCommand($mockDemand);
	}
}
