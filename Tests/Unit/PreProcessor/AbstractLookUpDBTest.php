<?php
namespace CPSIT\T3import\Tests\PreProcessor;

use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;

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
 * Class LookUpSourceDBTest
 *
 * @package CPSIT\T3import\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3import\PreProcessor\AbstractLookUpDB
 */
class AbstractLookUpDBTest extends BaseTestCase {

	/**
	 * @var \CPSIT\T3import\PreProcessor\AbstractLookUpDB
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock('CPSIT\\T3import\\PreProcessor\\AbstractLookUpDB',
			['getQueryConfiguration'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::injectDatabaseConnectionService
	 */
	public function injectDatabaseConnectionServiceForObjectSetsConnectionService() {
		$expectedConnectionService = $this->getAccessibleMock('CPSIT\\T3import\\Service\\DatabaseConnectionService',
			['dummy'], [], '', FALSE);

		$this->subject->injectDatabaseConnectionService($expectedConnectionService);

		$this->assertSame(
			$expectedConnectionService,
			$this->subject->_get('connectionService')
		);
	}


	/**
	 * @test
	 */
	public function processSetsDatabase() {
		$configuration = [
			'identifier' => 'fooDatabase'
		];
		$connectionService = $this->getAccessibleMock('CPSIT\\T3import\\Service\\DatabaseConnectionService',
			['getDatabase'], [], '', FALSE);
		$connectionService->expects($this->once())
			->method('getDatabase')
			->with($configuration['identifier']);
		$record = [];
		$this->subject->injectDatabaseConnectionService($connectionService);

		$this->subject->process($configuration, $record);
	}

}
