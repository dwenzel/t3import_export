<?php
namespace CPSIT\T3import\Component\Initializer;

use CPSIT\T3import\ConfigurableInterface;
use CPSIT\T3import\ConfigurableTrait;
use CPSIT\T3import\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

/**
 * Class TruncateTables
 * truncates given tables either from default database or a
 * database registered with DatabaseConnectionService by identifier
 *
 * @package CPSIT\T3import\Component\Initializer
 */
class TruncateTables
	extends AbstractInitializer
	implements InitializerInterface, ConfigurableInterface {
	use ConfigurableTrait;

	/**
	 * @var \CPSIT\T3import\Service\DatabaseConnectionService
	 */
	protected $connectionService;

	/**
	 * @var DatabaseConnection
	 */
	protected $database;

	/**
	 * Constructor
	 */
	public function __construct() {
		if (!$this->database instanceof DatabaseConnection) {
			$this->database = $GLOBALS['TYPO3_DB'];
		}
	}

	/**
	 * @param \CPSIT\T3import\Service\DatabaseConnectionService $dbConnectionService
	 */
	public function injectDatabaseConnectionService(DatabaseConnectionService $dbConnectionService) {
		$this->connectionService = $dbConnectionService;
	}

	/**
	 * @param array $configuration
	 * @param array $records Array with prepared records
	 * @return bool
	 */
	public function process($configuration, &$records) {
		if (isset($configuration['identifier'])) {
			$this->database = $this->connectionService
				->getDatabase($configuration['identifier']);
		}
		$tables = GeneralUtility::trimExplode(
			',',
			$configuration['tables'],
			true
		);
		foreach ($tables as $table) {
			$this->database->exec_TRUNCATEquery($table);
		}
	}

	/**
	 * Tells whether the given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration) {
		if (!isset($configuration['tables'])
			|| !is_string($configuration['tables'])) {
			return false;
		}
		if (isset($configuration['identifier'])
			AND !is_string($configuration['identifier'])
		) {
			return false;
		}
		if (isset($configuration['identifier'])
			AND !$this->connectionService->isRegistered($configuration['identifier'])
		) {
			return false;
		}

		return true;
	}
}