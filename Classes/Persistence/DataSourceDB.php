<?php
namespace CPSIT\T3import\Persistence;

use CPSIT\T3import\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class DataSourceDB implements DataSourceInterface {

	/**
	 * @var \CPSIT\T3import\Service\DatabaseConnectionService
	 */
	protected $connectionService;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $database;

	/**
	 * Unique identifier of the database connection to use.
	 * This connection must be registered with the connection service.
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * Constructor
	 *
	 * @param string $identifier Unique identifier of a database connection as registered with DatabaseConnectionService
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @param DatabaseConnectionService $connectionService
	 */
	public function injectDatabaseConnectionService(DatabaseConnectionService $connectionService) {
		$this->connectionService = $connectionService;
	}

	/**
	 * Gets the database connection
	 *
	 * @return DatabaseConnection
	 * @throws \CPSIT\T3import\Service\MissingDatabaseException
	 */
	public function getDatabase() {
		if (!$this->database instanceof DatabaseConnection) {
			$this->database = $this->connectionService->getDatabase($this->identifier);
		}

		return $this->database;
	}


	/**
	 * Fetches records from the database
	 *
	 * @param array $configuration source query configuration
	 * @return array Array of records from database or empty array
	 */
	public function getRecords(array $configuration) {
		$records = $this->getDatabase()->exec_SELECTgetRows(
			$configuration['fields'],
			$configuration['table'],
			$configuration['where'],
			$configuration['groupBy'],
			$configuration['orderBy'],
			$configuration['limit']
		);
		if ($records !== null) {
			return $records;
		} else {
			return [];
		}
	}

	/**
	 * Gets the identifier
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}
}