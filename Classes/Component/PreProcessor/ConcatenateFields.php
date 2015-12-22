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
 * Class ConcatenateFields
 * Concatenates fields of a given record and sets the result
 * into a new or existing field of this record
 *
 * @package CPSIT\T3import\PreProcessor
 */
class ConcatenateFields
	extends AbstractPreProcessor
	implements PreProcessorInterface {

	/**
	 * @param array $configuration
	 * @param array $record
	 * @return void
	 */
	public function process($configuration, &$record) {
		$targetFieldName = $configuration['targetField'];
		foreach ($configuration['fields'] as $key => $value) {
			if (isset($value['wrap'])
				AND !empty($record[$key])
			) {
				$record[$key] = $this->contentObjectRenderer->wrap(
					$record[$key],
					$value['wrap']
				);
			}
			if (isset($value['noTrimWrap'])
				AND !empty($record[$key])
			) {
				$record[$key] = $this->contentObjectRenderer->noTrimWrap(
					$record[$key],
					$value['noTrimWrap']
				);
			}
			$record[$targetFieldName] .= $record[$key];
		}

	}

	/**
	 * Tells if a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid($configuration) {
		if (!isset($configuration['targetField'])
			OR !is_string($configuration['targetField'])
		) {
			return FALSE;
		}
		if (!isset($configuration['fields'])
			OR !is_array($configuration['fields'])
		) {
			return FALSE;
		}

		return TRUE;
	}
}
