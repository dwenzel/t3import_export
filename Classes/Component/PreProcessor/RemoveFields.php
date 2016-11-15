<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

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
 * Class RemoveFields
 * Removes fields from an incoming array (recursively)
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class RemoveFields
	extends AbstractPreProcessor
	implements PreProcessorInterface
{

	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration)
	{
		if (!isset($configuration['fields'])) {
			return false;
		}
		if (!is_array($configuration['fields'])) {
			return false;
		}
		foreach ($configuration['fields'] as $field => $value) {

			if (!$this->validateFieldsList($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * validate config array recursively
	 *
	 * @param $fieldListConfig
	 * @return bool
	 */
	protected function validateFieldsList($fieldListConfig)
	{
		if(is_array($fieldListConfig) && isset($fieldListConfig['children'])) {
			foreach ($fieldListConfig['children'] as $field => $value) {

				if (!$this->validateFieldsList($value)) {
					return false;
				}
			}
			return true;
		} elseif (is_array($fieldListConfig)) {
			foreach ($fieldListConfig as $field => $value) {

				if (!$this->validateFieldsList($value)) {
					return false;
				}
			}
			return true;
		}

		return ((is_bool($fieldListConfig) && $fieldListConfig) || $fieldListConfig == 'true');
	}

	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	public function process($configuration, &$record)
	{
		$fields = $configuration['fields'];
		$this->removeFieldInArray($record, $fields);
		
		return true;
	}

	/**
	 * remove array nodes with an config
	 *
	 * @param array $fieldArray
	 * @param array $subConfig
	 */
	protected function removeFieldInArray(&$fieldArray, $subConfig)
	{
		// iterate through the config - we only need to compute the config not the entire record
		foreach ($subConfig as $field => $value) {
			// if config node not matching with record - skip this step
			if(!isset($fieldArray[$field])) {
				continue;
			}

			// if the config says "it goes down there!" and the record is also an subArray fire the recursiveCall
			if (is_array($value) && is_array($fieldArray[$field]) && isset($value['children'])) {
				$childCount = count($fieldArray[$field]);
				for ($i = 0; $i < $childCount; ++$i) {
					$this->removeFieldInArray($fieldArray[$field][$i], $value['children']);
				}
			} elseif (is_array($value) && is_array($fieldArray[$field])) {
				// if assoc array do it direct
				$this->removeFieldInArray($fieldArray[$field], $value);
			} else {
				// remove field from record
				unset($fieldArray[$field]);
			}
		}
	}
}
