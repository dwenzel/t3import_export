<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use CPSIT\T3importExport\Component\Initializer\DeleteFromTable;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class DeleteFromTableTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Component\Initializer\DeleteFromTable|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(DeleteFromTable::class,
			['dummy'], [], '', false);
	}

	/**
	 * @test
	 */
	public function injectDatabaseConnectionServiceForObjectSetsConnectionService() {
		/** @var DatabaseConnectionService $expectedConnectionService */
		$expectedConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
			['dummy'], [], '', false);

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
		    'table' => 'foo',
            'fields' => 'bar',
            'rows' => [],
			'identifier' => 'fooDatabase'
		];
        $mockDatabase = $this->getMock(
            DatabaseConnection::class, ['exec_DELETEquery'], [], '', false);
        /** @var DatabaseConnectionService $connectionService|\PHPUnit_Framework_MockObject_MockObject */
		$connectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
			['getDatabase'], [], '', false);
		$connectionService->expects($this->once())
			->method('getDatabase')
			->with($configuration['identifier'])
            ->will($this->returnValue($mockDatabase));

		$record = [];
		$this->subject->injectDatabaseConnectionService($connectionService);

		$this->subject->process($configuration, $record);
	}

	/**
	 * @test
	 */
	public function isConfigurationValidReturnsFalseIfTableIsNotSet() {
		$mockConfiguration = [];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 */
	public function isConfigurationValidReturnsFalseIfTableIsNotString() {
		$mockConfiguration = [
			'table' => 1
		];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfWhereIsNotSet() {
        $mockConfiguration = [
            'table' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfWhereIsNotString() {
        $mockConfiguration = [
            'table' => 'foo',
            'where' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfIdentifierIsNotString() {
        $mockConfiguration = [
            'table' => 'foo',
            'where' => 'bar',
            'identifier' => 2
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
	 * @test
	 */
	public function isConfigurationValidReturnsTrueForValidConfiguration() {
		$validConfiguration = [
			'table' => 'tableName',
            'where' => 'foo,bar',
		];
        $this->assertTrue(
			$this->subject->isConfigurationValid($validConfiguration)
		);
	}

	/**
	 * @test
	 */
	public function isConfigurationValidReturnsFalseIfDatabaseIsNotRegistered() {
		/** @var DatabaseConnectionService $mockConnectionService */
		$mockConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
			[], [], '', false);

		$this->subject->injectDatabaseConnectionService($mockConnectionService);

		$mockConfiguration = [
			'identifier' => 'missingDatabaseIdentifier',
			'table' => 'fooTable',
            'where' => 'bar',
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

	/**
	 * @test
	 */
	public function processDeletesRecordsFromTable() {
		$tableName = 'fooTable';
		$where = 'foo=bar';
		$config = [
			'table' => $tableName,
            'where' => $where,
		];
		$records = [];
		$mockDatabase = $this->getMock(
			DatabaseConnection::class, ['exec_DELETEquery']
		);
		$mockDatabase->expects($this->once())
			->method('exec_DELETEquery')
			->with($tableName);
		$this->subject->_set('database', $mockDatabase);

		$this->subject->process($config, $records);
	}
}
