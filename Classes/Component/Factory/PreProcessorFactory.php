<?php
namespace CPSIT\T3import\Component\Factory;

use CPSIT\T3import\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3import\Factory\AbstractFactory;
use CPSIT\T3import\Service\InvalidConfigurationException;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class PreProcessorFactory extends AbstractFactory {
	/**
	 * Builds a PreProcessor object
	 *
	 * @param array $settings
	 * @param string $identifier
	 * @throws InvalidConfigurationException
	 * @return \CPSIT\T3import\Component\PreProcessor\PreProcessorInterface
	 */
	public function get(array $settings, $identifier = NULL) {
		$additionalInformation = '.';
		if (!is_null($identifier)) {
			$additionalInformation = ' for ' . $identifier;
		}
		if (!isset($settings['class'])) {
			throw new InvalidConfigurationException(
				'Missing class in pre processor configuration' . $additionalInformation,
				1447427020
			);
		}
		$className = $settings['class'];

		if (!class_exists($className)) {
			throw new InvalidConfigurationException(
				'Pre-processor class ' . $className . ' in configuration for' . $additionalInformation
				. ' does not exist.',
				1447427184
			);
		}

		if (!in_array(PreProcessorInterface::class, class_implements($className))) {
			throw new InvalidConfigurationException(
				'Pre-processor class ' . $className . ' in configuration for' . $additionalInformation
				. ' must implement PreProcessorInterface.',
				1447428235
			);
		}

		return $this->objectManager->get($className);
	}

}