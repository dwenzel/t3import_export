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

/**
 * Class MapFields
 * Maps one field of a record to another. Existing fields are overwritten!
 *
 * @package CPSIT\T3import\PreProcessor
 */
class MapFields
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
		if (!is_array($configuration['fields'])) {
			return FALSE;
		}
		foreach ($configuration['fields'] as $field => $value) {
			if (!is_string($value)
				OR empty($value)
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
		foreach ($fields as $sourceField => $targetField) {
			$this->mapField($sourceField, $targetField, $record);
		}

		return TRUE;
	}

}