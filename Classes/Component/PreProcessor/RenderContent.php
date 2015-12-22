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
class RenderContent
	extends AbstractPreProcessor
	implements PreProcessorInterface {
	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	function process($configuration, &$record) {
		$this->renderFields($configuration, $record);

		return TRUE;
	}

	/**
	 * Tells whether a given configuration is valid
	 *
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
			if (!is_array($value)
				OR empty($value)
			) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param $configuration
	 * @param $record
	 * @return array
	 */
	protected function renderFields($configuration, &$record) {
		foreach ($configuration['fields'] as $fieldName => $localConfiguration) {
			if (isset($localConfiguration['multipleRows'])) {
				$childRecords = $record[$fieldName];
				if (!is_array($childRecords)) {
					continue;
				}
				foreach ($childRecords as $key => &$childRecord) {
					$this->renderFields($localConfiguration, $childRecord);
				}
				$record[$fieldName] = $childRecords;
			} elseif (isset($localConfiguration['singleRow'])) {
				$record[$fieldName] = $this->renderFields($localConfiguration, $record[$fieldName]);
			} else {
				$record[$fieldName] = $this->renderContent($record, $localConfiguration);
			}
		}
	}

}