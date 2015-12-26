<?php
namespace CPSIT\T3import\Component\Converter;

use CPSIT\T3import\Persistence\MissingClassException;
use CPSIT\T3import\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3import\Service\InvalidConfigurationException;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

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
class ArrayToDomainObject
	extends AbstractConverter
	implements ConverterInterface{

	/**
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var PropertyMappingConfiguration
	 */
	protected $propertyMappingConfiguration;

	/**
	 * @var PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	/**
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * injects the property mapper
	 *
	 * @param PropertyMapper $propertyMapper
	 */
	public function injectPropertyMapper(PropertyMapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * injects the property mapping configuration builder
	 *
	 * @param PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder
	 */
	public function injectPropertyMappingConfigurationBuilder(PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder) {
		$this->propertyMappingConfigurationBuilder = $propertyMappingConfigurationBuilder;
	}

	/**
	 * injects the object manager
	 *
	 * @param ObjectManager $objectManager
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Converts the record
	 *
	 * @param array $configuration
	 * @param array $record
	 * @return DomainObjectInterface
	 */
	public function convert(array $record, array $configuration) {
		$mappingConfiguration = $configuration;
		unset($mappingConfiguration['targetClass']);
		$mappingConfiguration = $this->getMappingConfiguration($mappingConfiguration);
		return $this->propertyMapper->convert(
			$record,
			$configuration['targetClass'],
			$mappingConfiguration
		);
	}

	/**
	 * @param array $configuration
	 * @throws InvalidConfigurationException
	 * @throws MissingClassException
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration) {
		$this->validateTargetClass($configuration);
		return $this->validatePropertyConfiguration($configuration);
	}

	/**
	 * @param array|null $configuration Configuration array from TypoScript
	 * @return PropertyMappingConfiguration
	 */
	public function getMappingConfiguration($configuration = null) {
		if ($this->propertyMappingConfiguration instanceof PropertyMappingConfiguration) {
			return $this->propertyMappingConfiguration;
		}

		if (empty($configuration)) {
			$propertyMappingConfiguration = $this->getDefaultMappingConfiguration();

		} else {
			$propertyMappingConfiguration = $this->propertyMappingConfigurationBuilder
				->build($configuration);
		}
		$this->propertyMappingConfiguration = $propertyMappingConfiguration;

		return $propertyMappingConfiguration;
	}

	/**
	 * @return PropertyMappingConfiguration
	 */
	protected function getDefaultMappingConfiguration() {
		/** @var PropertyMappingConfiguration $propertyMappingConfiguration */
		$propertyMappingConfiguration = $this->objectManager->get(
			PropertyMappingConfiguration::class
		);
		$propertyMappingConfiguration->setTypeConverterOptions(
			PersistentObjectConverter::class,
			[
				PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
				PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
			]
		)->skipUnknownProperties();

		return $propertyMappingConfiguration;
	}

	/**
	 * @param array $configuration
	 * @throws InvalidConfigurationException
	 * @throws MissingClassException
	 */
	protected function validateTargetClass(array $configuration) {
		if (!isset($configuration['targetClass'])) {
			throw new InvalidConfigurationException (
				'Invalid configuration for ' . __CLASS__ .
				'. Missing targetClass option',
				1451146126
			);
		}
		if (!is_string($configuration['targetClass'])) {
			throw new InvalidConfigurationException(
				'Invalid configuration for ' . __CLASS__ .
				'. Option value for targetClass must be a string.',
				1451146384
			);
		}
		if (!class_exists($configuration['targetClass'])) {
			throw new MissingClassException(
				'Invalid configuration for ' . __CLASS__ .
				'. Target class does not exist.',
				1451146564
			);
		}
	}

	/**
	 * @param array $configuration
	 * @return bool
	 * @throws InvalidConfigurationException
	 */
	protected function validatePropertyConfiguration(array $configuration) {
		if (isset($configuration['allowProperties'])
			AND !is_string($configuration['allowProperties'])
		) {
			throw new InvalidConfigurationException(
				'Invalid configuration for ' . __CLASS__ .
				'. Option value allowProperties must be a comma separated
				 string of property names.',
				1451146869
			);
		}
		if (isset($configuration['properties'])) {
			if (!is_array($configuration['properties'])
			) {
				throw new InvalidConfigurationException(
					'Invalid configuration for ' . __CLASS__ .
					'. Option value properties must be an array.',
					1451147517
				);
			}

			foreach ($configuration['properties'] as $propertyName => $localConfiguration) {
				$this->validatePropertyConfigurationRecursive($localConfiguration);
			}
		}

		/**
		 * todo:
		 * children.maxItems: int
		 * typeConverter.class: set, string, class exists
		 * typeConverter.options: ?
		 **/

		return TRUE;
	}

	/**
	 * @param array $localConfiguration
	 * @throws InvalidConfigurationException
	 */
	protected function validatePropertyConfigurationRecursive(array $localConfiguration) {
		$this->validatePropertyConfiguration($localConfiguration);
		if (isset($localConfiguration['children'])) {
			if (!isset($localConfiguration['children']['maxItems'])) {
				throw new InvalidConfigurationException(
					'Invalid configuration for ' . __CLASS__ .
					'. children.maxItems must be set.',
					1451157586
				);
			}
			foreach($localConfiguration['children']['properties'] as $child=>$childConfiguration) {
				$this->validatePropertyConfiguration($childConfiguration);
			}
		}
	}
}