<?php
namespace CPSIT\T3import\Property;

use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
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
class PropertyMappingConfigurationBuilder {

	/**
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * injects the object manager
	 *
	 * @param ObjectManager $objectManager
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param $configuration
	 * @return PropertyMappingConfiguration
	 */
	public function build($configuration) {
		/** @var PropertyMappingConfiguration $propertyMappingConfiguration */
		$propertyMappingConfiguration = $this->objectManager->get(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration'
		);
		$this->configure($configuration, $propertyMappingConfiguration);

		return $propertyMappingConfiguration;
	}

	/**
	 * Gets the type converter class
	 * Default class name is returned if not set in configuration
	 *
	 * @param $configuration
	 * @return string
	 */
	protected function getTypeConverterClass($configuration) {
		if (isset($configuration['typeConverter']['class'])
			AND is_string($configuration['typeConverter']['class'])
		) {
			return $configuration['typeConverter']['class'];
		}

		return 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter';
	}

	/**
	 * Gets the type converter options
	 * Default options are returned if not set in configuration
	 *
	 * @param $configuration
	 * @return array
	 */
	protected function getTypeConverterOptions($configuration) {
		$options = [
			PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
			PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
		];

		if (isset($configuration['typeConverter']['options'])
			AND is_array($configuration['typeConverter']['options'])
		) {
			$options = ArrayUtility::arrayMergeRecursiveOverrule(
				$options,
				$configuration['typeConverter']['options']
			);
		}

		return $options;
	}

	/**
	 * Gets the allowed properties from configuration
	 * An empty array is returned if not set
	 *
	 * @param $configuration
	 * @return array
	 */
	protected function getAllowedProperties($configuration) {
		if (isset($configuration['allowProperties'])
			AND is_string($configuration['allowProperties'])
		) {
			$allowedProperties = ArrayUtility::trimExplode(
				',',
				$configuration['allowProperties'],
				TRUE
			);

			return $allowedProperties;
		}

		return [];
	}

	/**
	 * Tells if all properties should be allowed to map
	 *
	 * @param $configuration
	 * @return bool
	 */
	protected function getAllowAllProperties($configuration) {
		if (isset($configuration['allowAllProperties'])
			AND (bool) $configuration['allowAllProperties']
		) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param array $configuration
	 * @param PropertyMappingConfiguration $propertyMappingConfiguration
	 */
	protected function configure($configuration, $propertyMappingConfiguration) {
		$propertyMappingConfiguration->setTypeConverterOptions(
			$this->getTypeConverterClass($configuration),
			$this->getTypeConverterOptions($configuration)
		);

		$propertyMappingConfiguration->skipUnknownProperties();
		if ($this->getAllowAllProperties($configuration)) {
			$propertyMappingConfiguration->allowAllProperties();
		} else {
			$allowedProperties = $this->getAllowedProperties($configuration);
			if ((bool) $allowedProperties) {
				call_user_func_array(
					[$propertyMappingConfiguration, 'allowProperties'],
					$allowedProperties
				);
			}
		}
		if ((bool) $properties = $this->getProperties($configuration)) {
			$allowedProperties = $this->getAllowedProperties($configuration);
			foreach ($properties as $propertyName => $localConfiguration) {
				if (!in_array($propertyName, $allowedProperties)) {
					continue;
				}

				$this->configure(
					$localConfiguration,
					$propertyMappingConfiguration->forProperty($propertyName)
				);

				if (!(isset($localConfiguration['children'])
					AND isset($localConfiguration['children']['maxItems']))
				) {
					continue;
				}
				$maxItems = (int) $localConfiguration['children']['maxItems'];
				for ($child = 0; $child <= $maxItems; $child++) {
					$propertyPath = $propertyName . '.' . $child;
					$this->configure(
						$localConfiguration['children'],
						$propertyMappingConfiguration->forProperty($propertyPath)
					);
				}
			}
		}

	}

	/**
	 * @param $configuration
	 * @return array
	 */
	protected function getProperties($configuration) {
		$properties = [];
		if (isset($configuration['properties'])
			AND is_array($configuration['properties'])
		) {
			$properties = $configuration['properties'];

		}

		return $properties;
	}
}