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
class AddArrays
	extends AbstractPreProcessor
	implements PreProcessorInterface {
	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration) {
		if (!isset($configuration['targetField'])
			OR !is_string($configuration['targetField'])
		) {
			return FALSE;
		}
		if (!isset($configuration['fields'])
			OR !is_string($configuration['fields'])
		) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	public function process($configuration, &$record) {
		$fields = explode(',', $configuration['fields']);
		$targetField = $configuration['targetField'];

		foreach ($fields as $field) {
			if (isset($record[$field])
				AND is_array($record[$field])
			) {
				foreach ($record[$field] as $value) {
					$record[$targetField][] = $value;
				}
			}
		}

		return TRUE;
	}
}
