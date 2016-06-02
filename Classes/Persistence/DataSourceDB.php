<?php
namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\RenderContentInterface;
use CPSIT\T3importExport\RenderContentTrait;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
class DataSourceDB
	implements DataSourceInterface, IdentifiableInterface, RenderContentInterface, DataSourceQueueInterface {
	use IdentifiableTrait, ConfigurableTrait, RenderContentTrait;

	/**
	 * @var \CPSIT\T3importExport\Service\DatabaseConnectionService
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
	 * @param DatabaseConnectionService $connectionService
	 */
	public function injectDatabaseConnectionService(DatabaseConnectionService $connectionService)
    {
		$this->connectionService = $connectionService;
	}

	/**
	 * Gets the database connection
	 *
	 * @return DatabaseConnection
	 * @throws \CPSIT\T3importExport\MissingDatabaseException
	 */
	public function getDatabase()
    {
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
	public function getRecords(array $configuration)
    {
		$queryConfiguration = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => ''
		];

		ArrayUtility::mergeRecursiveWithOverrule(
			$queryConfiguration,
			$configuration,
			TRUE,
			FALSE
		);

        foreach($queryConfiguration as $key=>$value) {
            if (is_array($value)) {
                $renderedValue = $this->renderContent([], $value);
                if (!is_null($renderedValue)) {
                    $queryConfiguration[$key] = $renderedValue;
                }
            }
        }
		$records = $this->getDatabase()->exec_SELECTgetRows(
			$queryConfiguration['fields'],
			$queryConfiguration['table'],
			$queryConfiguration['where'],
			$queryConfiguration['groupBy'],
			$queryConfiguration['orderBy'],
			$queryConfiguration['limit']
		);
		if ($records !== null) {
			return $records;
		}

		return [];
	}

	/**
	 * Tells if a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration)
    {
		if (!isset($configuration['table'])
			OR !is_string($configuration['table'])) {
			return false;
		}
		return true;
	}

    /**
     * @param array $configuration
     * @param int $batchSize = 0
     * @param int $currentOffset = 0
     * @param bool $eof = false
     * @return mixed
     */
    public function getRecordsIndexes(array $configuration, $batchSize = 0, $currentOffset = 0, &$eof = false)
    {
        // manipulate config
        $configuration['fields'] = 'uid';

        // rebuild limit only if necessary
        if ($batchSize > 0) {
            // pre-init config values
            $configOffset = 0;
            $configLimit = 0;

            // check if limit is in task isset
            if (isset($configuration['limit'])) {
                // parse limit config
                list($configOffset, $configLimit) = explode(',', $configuration['limit']);
                // if only config isset (no offset) remap values
                if ($configLimit == null) {
                    $configLimit = $configOffset;
                    $configOffset = 0;
                }
            }
            // adjust static offset from task config with dynamic offset from queue
            $currentOffset += $configOffset;

            // adjust limit with static config limit
            // if the next calculated offset greater the static limit
            // calculate the delta from currentOffset and static limit (how many are left)
            $finalEnd = $configLimit + $configOffset;
            if ($configLimit > 0 &&
                $currentOffset + $batchSize > $finalEnd
            ) {
                $batchSize = $finalEnd - $currentOffset;
            }

            // if the batch size <= 0 quick abort ...
            // we know at this point there aren't any records left
            if ($batchSize <= 0) {
                $eof = true;
                return [];
            }

            // write new limit statement
            $configuration['limit'] = $currentOffset . ', ' . $batchSize;
        }
        // load records with modified
        $records = $this->getRecords($configuration);

        // remap the output array to a simple index list
        $result = [];
        foreach($records as $record) {
            $result[] = $record['uid'];
        }

        // if there any records found ... we reach the end
        $eof = (bool)(count($result) == 0);

        return $result;
    }
}
