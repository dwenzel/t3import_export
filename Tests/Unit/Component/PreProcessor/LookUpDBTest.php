<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
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
 * Class LookUpDBTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\LookUpDB
 */
class LookUpDBTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Component\PreProcessor\LookUpDB
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(LookUpDB::class,
			['getQueryConfiguration'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::injectDatabaseConnectionService
	 */
	public function injectDatabaseConnectionServiceForObjectSetsConnectionService() {
		/** @var DatabaseConnectionService $expectedConnectionService */
		$expectedConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
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
		/** @var DatabaseConnectionService $connectionService */
		$connectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
			['getDatabase'], [], '', FALSE);
		$connectionService->expects($this->once())
			->method('getDatabase')
			->with($configuration['identifier']);
		$record = [];
		$this->subject->injectDatabaseConnectionService($connectionService);

		$this->subject->process($configuration, $record);
	}

	/**
	 * @test
	 */
	public function processGetsQueryConfiguration() {
		$configuration = [
			'select' => [
				'table' => 'fooTable'
			]
		];
		$expectedQueryConfiguration = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => '',
			'table' => 'fooTable'
		];
		$record = [];
		$this->subject = $this->getAccessibleMock(
			LookUpDB::class,
			['performQuery'], [], '', FALSE);

		$this->subject->expects($this->once())
			->method('performQuery')
			->with($expectedQueryConfiguration)
			->will($this->returnValue(null));

		$this->subject->process($configuration, $record);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfTargetFieldIsNotSet() {
		$mockConfiguration = ['foo'];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfTargetFieldIsNotString() {
		$mockConfiguration = [
			'targetField' => 1,
			'fields' => []
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfTableIsNotSet() {
		$mockConfiguration = [
			'select' => [

			]
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfTableIsNotString() {
		$mockConfiguration = [
			'select' => [
				'table' => 1
			]
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfSourceIsNotSet() {
		$mockConfiguration = [
			'targetField' => 'foo'
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfSourceIsNotArray() {
		$mockConfiguration = [
			'targetField' => 'foo',
			'source' => 'invalidStringValue'
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsTrueForValidConfiguration() {
		/** @var DatabaseConnectionService $mockConnectionService */
		$mockConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
			[], [], '', FALSE);

		$this->subject->injectDatabaseConnectionService($mockConnectionService);

		$validDatabaseIdentifier = 'fooDatabaseIdentifier';
		$validConfiguration = [
			'identifier' => $validDatabaseIdentifier,
			'select' => [
                'table' => 'tableName'
            ]
		];
        DatabaseConnectionService::register(
            $validDatabaseIdentifier,
            'hostname',
            'databaseName',
            'userName',
            'password'
        );

        $this->assertTrue(
			$this->subject->isConfigurationValid($validConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseForInvalidIdentifier() {
		$mockConfiguration = [
			'identifier' => [],
			'select' => [
				'table' => 'fooTable'
			],
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfDatabaseIsNotRegistered() {
		/** @var DatabaseConnectionService $mockConnectionService */
		$mockConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
			[], [], '', FALSE);

		$this->subject->injectDatabaseConnectionService($mockConnectionService);

		$mockConfiguration = [
			'identifier' => 'missingDatabaseIdentifier',
			'select' => [
				'table' => 'fooTable'
			],
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}


	/**
	 * @test
	 */
	public function constructorSetsDefaultDatabase() {
		$GLOBALS['TYPO3_DB'] = $this->getMock(
			DatabaseConnection::class, [], [], '', false
		);
		$this->subject->__construct();

		$this->assertSame(
			$GLOBALS['TYPO3_DB'],
			$this->subject->_get('database')
		);
	}
}
