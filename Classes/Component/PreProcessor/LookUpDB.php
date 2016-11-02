<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
 * Class LookUpDB
 * Base class for database look up.
 * Children must implement PreProcessorInterface
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class LookUpDB
	extends AbstractPreProcessor
	implements PreProcessorInterface {
	use DatabaseTrait;

	/**
	 * Tells if a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration) {
		if (!isset($configuration['select'])
			OR !is_array($configuration['select'])
		) {
			return FALSE;
		}
		if (!isset($configuration['select']['table'])
			OR !is_string(($configuration['select']['table']))
		) {
			return FALSE;
		}
		if (isset($configuration['identifier'])
			AND !is_string($configuration['identifier'])
		) {
			return FALSE;
		}
		if (isset($configuration['identifier'])
			AND !DatabaseConnectionService::isRegistered($configuration['identifier'])
		) {
			return FALSE;
		}
		if (!isset($configuration['targetField']) || !is_string($configuration['targetField'])) {
		    return false;
        }

		return TRUE;
	}

	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	public function process($configuration, &$record) {
		if (isset($configuration['identifier'])) {
			$this->database = $this->connectionService
				->getDatabase($configuration['identifier']);
		}
		if (isset($configuration['childRecords'])
			AND is_array($record[$configuration['childRecords']])
		) {
			$localConfiguration = $configuration;
			unset($localConfiguration['childRecords']);
			foreach ($record[$configuration['childRecords']] as &$childRecord) {
				$this->process($localConfiguration, $childRecord);
			}
			return TRUE;
		}
		$queryConfiguration = $this->getQueryConfiguration($configuration);
		$queryConfiguration = $this->parseQueryConstraints($record, $queryConfiguration);
		if ($queryConfiguration == FALSE) {
			return FALSE;
		}
		$queryResult = $this->performQuery($queryConfiguration);
		$targetFieldName = $configuration['targetField'];
		if ($queryResult) {
			if ($queryConfiguration['singleRow']) {
				$this->mapFields($record, $queryResult, $configuration);
			} else {
				$mappedRecords = [];
				foreach ($queryResult as $row) {
					$mappedRecord = [];
					$this->mapFields($mappedRecord, $row, $configuration);
					$mappedRecords[] = $mappedRecord;
				}
				$record[$targetFieldName] = $mappedRecords;
			}
		} elseif (isset($configuration['targetField'])
			AND is_string($configuration['targetField'])
		) {
			unset($record[$targetFieldName]);
		}

		return TRUE;
	}

	/**
	 * @param $configuration
	 * @return array
	 */
	protected function getQueryConfiguration($configuration) {
		$queryConfiguration = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => ''
		];
		ArrayUtility::mergeRecursiveWithOverrule(
			$queryConfiguration,
			$configuration['select'],
			TRUE,
			FALSE
		);

		return $queryConfiguration;
	}

	/**
	 * Parses the constraints of a query configuration into a
	 * WHERE clause
	 *
	 * @param $record
	 * @param $queryConfiguration
	 * @return array | FALSE Parsed query configuration
	 */
	protected function parseQueryConstraints(&$record, $queryConfiguration) {
		if (!empty($queryConfiguration['where'])) {
			if (is_array($queryConfiguration['where'])) {
				$whereClause = '';
				foreach ($queryConfiguration['where'] as $operator => $value) {
					if ($operator === 'AND' OR $operator === 'OR') {
						if ($whereClause == '' AND $operator === 'AND') {
							$operator = '';
						}
						$whereClause .= $operator . ' ' . $value['condition'];
						$prefix = '"';
						if (isset($value['prefix'])) {
							$prefix .= $value['prefix'];
						}

						if (isset($value['value'])) {
							//read field value from record
							$whereClause .= $prefix .
								$this->database->quoteStr(
									$record[$value['value']],
									$queryConfiguration['table']
								) . '"';
						}
					}
					if ($operator === 'IN') {
						if (isset($value['values'])
							AND isset($value['field'])
						) {
							$childConfig = $value['values'];

							if (is_array($childConfig)
								AND isset($childConfig['field'])
								AND isset($childConfig['value'])
								AND is_array($record[$childConfig['field']])
							) {
								$prefix = '"';
								if (isset($childConfig['prefix'])) {
									$prefix .= $childConfig['prefix'];
								}

								$whereClause .= ' ' . $value['field'] . ' IN (';
								$childValues = [];
								foreach ($record[$childConfig['field']] as $child) {
									$childValues[] = $prefix . $child[$childConfig['value']] . '"';
								}
								$whereClause .= implode(',', $childValues) . ')';
                                if (isset($value['keepOrder'])) {
                                    $whereClause .= ' ORDER BY FIELD (' . $value['field'] . ',' . implode(',', $childValues) . ')';
                                }
							} else {
								return FALSE;
							}
						}

					}
				}
				$queryConfiguration['where'] = $whereClause;
			}
		}

		return $queryConfiguration;
	}

	/**
	 * Maps fields
	 *
	 * @param array $record target record
	 * @param array $source Source: record
	 * @param array $config Mapping configuration
	 */
	protected function mapFields(&$record, $source, $config) {
		if (!isset($config['fields'])
			OR !is_array($config['fields'])
		) {
			return;
		}
		foreach ($config['fields'] as $fieldName => $singleConfig) {
			if (isset($singleConfig['mapTo'])
				AND is_string($singleConfig['mapTo'])
			) {
				$record[$singleConfig['mapTo']] = $source[$fieldName];
			}
		}
	}

	/**
	 * @param $queryConfiguration
	 * @return array|NULL
	 */
	protected function performQuery($queryConfiguration) {
		if ($queryConfiguration['singleRow']) {
			$queryResult = $this->database->exec_SELECTgetSingleRow(
				$queryConfiguration['fields'],
				$queryConfiguration['table'],
				$queryConfiguration['where'],
				$queryConfiguration['groupBy'],
				$queryConfiguration['orderBy']
			);
		} else {
			$queryResult = $this->database->exec_SELECTgetRows(
				$queryConfiguration['fields'],
				$queryConfiguration['table'],
				$queryConfiguration['where'],
				$queryConfiguration['groupBy'],
				$queryConfiguration['orderBy'],
				$queryConfiguration['limit']
			);
		}

		return $queryResult;
	}
}
