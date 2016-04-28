<?php
namespace CPSIT\T3importExport\Tests\Functional\Service;

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
use CPSIT\T3importExport\Service\ImportProcessor;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use CPSIT\ZewProjectconf\Service\ZewDbConnectionService;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportProcessorTest
 * Functional tests for CPSIT\T3importExport\Service\ImportProcessor
 *
 * @package CPSIT\T3importExport\Tests\Functional\Service
 */
class ImportProcessorTest extends FunctionalTestCase {

	/**
	 * @var ImportProcessor
	 */
	protected $importProcessor;

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = ['typo3/conf/ext/t3import_export'];

	public function setUp() {
		parent::setUp();
		$this->importProcessor = new \CPSIT\T3importExport\Service\ImportProcessor();
		/** @var ZewDbConnectionService $connectionService */
		$connectionService = $this->getMock(
			ZewDbConnectionService::class,
			[], [], '', FALSE
		);
		$connectionService->databaseHandle = $GLOBALS['TYPO3_DB'];
		$this->importProcessor->injectZewDbConnectionService($connectionService);
		$this->importDataSet(__DIR__ . '/../Fixtures/zew_imports_external_data.xml');
	}

	/**
	 * @test
	 */
	public function buildQueueFindsPublishedSeminars() {
		$expectedQueue = [
			[
				'seminar' => [
					[
						'id' => 1,
						'published' => 1,
						'titel_de' => 'findPublishedSeminars'
					]
				]
			]
		];

		$configuration = [

		];

		$this->importProcessor->buildQueue();

		$this->assertEquals(
			$expectedQueue,
			$this->importProcessor->getQueue()
		);
	}
}
