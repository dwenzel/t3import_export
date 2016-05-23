<?php

namespace CPSIT\T3importExport\Component\Converter;

use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
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
class ArrayToDataStream
    extends AbstractConverter
    implements ConverterInterface
{
    const BEFORE_CONVERT_SIGNAL = 'beforeConvertSignal';

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

    /**
     * injects the property mapper
     *
     * @param PropertyMapper $propertyMapper
     */
    public function injectPropertyMapper(PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * injects the property mapping configuration builder
     *
     * @param PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder
     */
    public function injectPropertyMappingConfigurationBuilder(
        PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder
    ) {
        $this->propertyMappingConfigurationBuilder = $propertyMappingConfigurationBuilder;
    }

    /**
     * injects the TargetClassConfigurationValidator
     *
     * @param TargetClassConfigurationValidator $validator
     */
    public function injectTargetClassConfigurationValidator(TargetClassConfigurationValidator $validator)
    {
        $this->targetClassConfigurationValidator = $validator;
    }

    /**
     * injects the MappingConfigurationValidator
     *
     * @param MappingConfigurationValidator $validator
     */
    public function injectMappingConfigurationValidator(MappingConfigurationValidator $validator)
    {
        $this->mappingConfigurationValidator = $validator;
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
        $this->emitSignal(self::BEFORE_CONVERT_SIGNAL,  $slotVariables);
        $data = $this->propertyMapper->convert(
            $record,
            $configuration['targetClass'],
            $mappingConfiguration
        );

        return $data;
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
}
