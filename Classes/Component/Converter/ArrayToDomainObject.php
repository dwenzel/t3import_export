<?php

namespace CPSIT\T3importExport\Component\Converter;

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

use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\ObjectManagerTrait;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * Class ArrayToDomainObject
 */
class ArrayToDomainObject extends AbstractConverter implements ConverterInterface
{
    use ObjectManagerTrait;

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
     * @var TargetClassConfigurationValidator
     */
    protected $targetClassConfigurationValidator;
    /**
     * @var MappingConfigurationValidator
     */
    protected $mappingConfigurationValidator;

    public function __construct(         PropertyMapper $propertyMapper = null,
    PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder = null,
    TargetClassConfigurationValidator $targetClassConfigurationValidator = null,
    MappingConfigurationValidator $mappingConfigurationValidator = null
    )
    {
        $this->propertyMapper = $propertyMapper ?? GeneralUtility::makeInstance(PropertyMapper::class);
        $this->propertyMappingConfigurationBuilder = $propertyMappingConfigurationBuilder ??
            GeneralUtility::makeInstance(PropertyMappingConfigurationBuilder::class);
        $this->targetClassConfigurationValidator = $targetClassConfigurationValidator ??
            GeneralUtility::makeInstance(TargetClassConfigurationValidator::class);
        $this->mappingConfigurationValidator = $mappingConfigurationValidator ??
            GeneralUtility::makeInstance(MappingConfigurationValidator::class);
    }

    /**
     * Converts the record
     *
     * @param array $configuration
     * @param array $record
     * @return DomainObjectInterface
     */
    public function convert(array $record, array $configuration)
    {
        $mappingConfiguration = $configuration;
        unset($mappingConfiguration['targetClass']);
        $mappingConfiguration = $this->getMappingConfiguration($mappingConfiguration);
        $slotVariables = [
            'configuration' => $configuration,
            'record' => $record
        ];
        return $this->propertyMapper->convert(
            $record,
            $configuration['targetClass'],
            $mappingConfiguration
        );
    }

    /**
     * @param array|null $configuration Configuration array from TypoScript
     * @return PropertyMappingConfiguration
     */
    public function getMappingConfiguration($configuration = null)
    {
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
     * @param PropertyMappingConfiguration $propertyMappingConfiguration
     */
    public function setPropertyMappingConfiguration(PropertyMappingConfiguration $propertyMappingConfiguration): void
    {
        $this->propertyMappingConfiguration = $propertyMappingConfiguration;
    }

    /**
     * @return PropertyMappingConfiguration
     */
    protected function getDefaultMappingConfiguration()
    {
        /** @var PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->objectManager->get(
            PropertyMappingConfiguration::class
        );
        $propertyMappingConfiguration->setTypeConverterOptions(
            PersistentObjectConverter::class,
            [
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
                PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
            ]
        )->skipUnknownProperties();

        return $propertyMappingConfiguration;
    }

    /**
     * @param array $configuration
     * @throws \CPSIT\T3importExport\InvalidConfigurationException
     * @throws MissingClassException
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        return (
            $this->targetClassConfigurationValidator->validate($configuration)
            && $this->mappingConfigurationValidator->validate($configuration)
        );
    }
}
