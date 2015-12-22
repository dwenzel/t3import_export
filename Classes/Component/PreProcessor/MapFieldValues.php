<?php
namespace CPSIT\T3import\Component\PreProcessor;

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
class MapFieldValues
	extends AbstractPreProcessor
	implements PreProcessorInterface {

	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid($configuration) {
		if (!isset($configuration['fields'])) {
			return FALSE;
		}
		if (isset($configuration['fields'])
			AND !is_array($configuration['fields'])
		) {
			return FALSE;
		}
		foreach ($configuration['fields'] as $field) {
			if (!isset($field['targetField'])
				OR !is_string(($field['targetField']))
			) {
				return FALSE;
			}
			if (!isset($field['values'])
				OR !is_array($field['values'])
			) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param array $configuration
	 * @param array $record
	 * @return TRUE
	 */
	public function process($configuration, &$record) {
		$fields = $configuration['fields'];
		foreach ($fields as $fieldName => $localConfig) {
			$this->mapValues($fieldName, $localConfig, $record);
		}

		return TRUE;
	}

	/**
	 * @param string $fieldName
	 * @param array $config
	 * @param array $record
	 */
	protected function mapValues($fieldName, $config, &$record) {
		foreach ($config['values'] as $sourceValue => $targetValue) {
			if ($record[$fieldName] == $sourceValue) {
				$record[$config['targetField']] = $targetValue;
			}
		}
	}
}