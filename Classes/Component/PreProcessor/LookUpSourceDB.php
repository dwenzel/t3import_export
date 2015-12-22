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
class LookUpSourceDB extends AbstractLookUpDB
	implements PreProcessorInterface {
	/**
	 * Tells if a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid($configuration) {
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
		if (!isset($configuration['identifier'])
			OR !is_string($configuration['identifier'])
		) {
			return FALSE;
		}
		if (!$this->connectionService->isRegistered($configuration['identifier'])) {
			return FALSE;
		}

		return TRUE;
	}

}