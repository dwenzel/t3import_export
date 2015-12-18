<?php
namespace CPSIT\T3import\PreProcessor;

use CPSIT\T3import\PreProcessor\AbstractPreProcessor;
use CPSIT\T3import\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;

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
class SetFieldValue
	extends AbstractPreProcessor
	implements PreProcessorInterface {

	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid($configuration) {
		if (!isset($configuration['targetField'])) {
			return FALSE;
		}
		if (!isset($configuration['value'])
			OR !is_string($configuration['value'])
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
		$record[$configuration['targetField']] = $configuration['value'];

		return TRUE;
	}

}