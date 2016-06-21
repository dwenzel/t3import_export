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
 * Maps one field of a record to another. Existing fields are overwritten!
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class XMLMapper
	extends AbstractPreProcessor
	implements PreProcessorInterface
{

	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration)
	{
		if (empty($configuration['fields'])) {
			return false;
		}
		if (!is_array($configuration['fields'])) {
			return false;
		}

		foreach ($configuration['fields'] as $field => $value) {

			if (!$this->validateFieldsList($field, $value)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * @param string $field
	 * @param array|string $value
	 * @return bool
	 */
	protected function validateFieldsList($field, $value)
	{
		if(is_array($value) && isset($value['children'])) {
			foreach ($value['children'] as $subField => $subValue) {

				if (!$this->validateFieldsList($subField, $subValue)) {
					return false;
				}
			}
			return true;
		} elseif(is_array($value)) {
			foreach ($value as $subField => $subValue) {

				if (!$this->validateFieldsList($subField, $subValue)) {
					return false;
				}
			}
			return true;
		} elseif ($value === '@attribute' || $field === 'mapTo') {
			return true;
		}

		return false;
	}

	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	public function process($configuration, &$record)
	{
		$fields = $configuration['fields'];
		$record = $this->remapXMLStructure($record, $fields);
		
		return true;
	}

	/**
	 * remove array nodes with an config
	 *
	 * @param array $fieldArray
	 * @param array $subConfig
	 */
	protected function remapXMLStructure($fieldArray, $subConfig)
	{
		foreach ($subConfig as $configKey => $value) {
			// map attributes
			if ($configKey !== 'mapTo' && isset($fieldArray[$configKey]) && !is_array($fieldArray[$configKey])) {
				$fieldArray = $this->mapAttributeInArray($fieldArray, $configKey);
			} elseif ($configKey === 'mapTo') {
				$fieldArray = $this->mapMapToInArray($fieldArray, $value);
			} elseif (isset($fieldArray[$configKey]) && is_array($fieldArray[$configKey])) {
				// check for mapTo here too
				if (isset($value['children'])) {
					$subNodes = $fieldArray[$configKey];
					foreach($subNodes as $nodeKey => $subNode) {
						$fieldArray[$configKey][$nodeKey] = $this->remapXMLStructure($subNode, $value['children']);
					}
					if (isset($value['mapTo'])) {
						$fieldArray[$configKey] = $this->mapMapToInArray($fieldArray[$configKey], $value['mapTo']);
					}
				} else {
					$fieldArray[$configKey] = $this->remapXMLStructure($fieldArray[$configKey], $value);
				}

			}
		}
		return $fieldArray;
	}

	protected function mapMapToInArray($array, $value)
	{
		$array['@mapTo'] = $value;
		return $array;
	}

	protected function mapAttributeInArray($array, $mapKey)
	{
		if (isset($array[$mapKey])) {
			if (!isset($array['@attribute'])) {
				$array['@attribute'] = [];
			}

			$array['@attribute'][$mapKey] = $array[$mapKey];
			unset($array[$mapKey]);
		}
		return $array;
	}
}
