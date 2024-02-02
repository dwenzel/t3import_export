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
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
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
 * Class ArrayToXMLStream
 */
class ArrayToXMLStream extends AbstractConverter implements ConverterInterface
{
    final public const DEFAULT_NODE_NAME = 'row';
    final public const XML_CONFIG_NODE_KEY = 'nodeName';
    final public const XML_CONFIG_FIELD_KEY = 'fields';

    final public const XML_CONFIG_FIELD_ATTR = '@attribute';
    final public const XML_CONFIG_FIELD_MAP = '@mapTo';
    final public const XML_CONFIG_FIELD_VALUE = '@value';
    final public const XML_CONFIG_FIELD_CDATA = '@cdata';
    final public const XML_CONFIG_FIELD_SEPARATE_ROW = '@separateRow';

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
        // setup config
        $rootEnclosure = $this->getRootEnclosureConfiguration($configuration);
        $fieldsConfig = $this->getFieldsConfiguration($configuration);
        // build xml node buffer
        $buffer = $this->generateXMLStream($record, $rootEnclosure, $fieldsConfig);

        // fetch target class (DataStream) if not set return xml buffer instead
        $result = GeneralUtility::makeInstance($configuration['targetClass']);
        if ($result instanceof DataStreamInterface) {
            $result->setStreamBuffer($buffer);
        } else {
            $result = $buffer;
        }
        return $result;
    }

    /**
     * @param array $configuration
     * @return string
     */
    private function getRootEnclosureConfiguration($configuration, $default = self::DEFAULT_NODE_NAME)
    {
        if (isset($configuration) && isset($configuration[static::XML_CONFIG_NODE_KEY])) {
            $default = $configuration[static::XML_CONFIG_NODE_KEY];
        }
        return $default;
    }

    /**
     * @param array $configuration
     * @return array|null
     */
    private function getFieldsConfiguration($configuration = null)
    {
        $fieldsConfiguration = null;
        if (isset($configuration) && isset($configuration[static::XML_CONFIG_FIELD_KEY])) {
            $fieldsConfiguration = $configuration[static::XML_CONFIG_FIELD_KEY];
        }
        return $fieldsConfiguration;
    }

    /**
     * @param array $data
     * @param $enclosure
     * @param null|string $fieldsConfig
     * @return string
     */
    protected function generateXMLStream(array $data, $enclosure, $fieldsConfig = null)
    {
        // init xmlBuilder (XMLWriter)
        $xml = new \XMLWriter();
        $xml->openMemory();

        if (isset($data[static::XML_CONFIG_FIELD_MAP])) {
            $enclosure = $data[static::XML_CONFIG_FIELD_MAP];
            unset($data[static::XML_CONFIG_FIELD_MAP]);
        }

        $xml->startElement($enclosure);

        if (!empty($data[static::XML_CONFIG_FIELD_ATTR])) {
            $this->writeAttributes($xml, $data[static::XML_CONFIG_FIELD_ATTR]);
            unset($data[static::XML_CONFIG_FIELD_ATTR]);
        }


        foreach ($data as $key => $sub) {
            $nodeConfig = null;
            if (isset($fieldsConfig[$key])) {
                $nodeConfig = $fieldsConfig[$key];
            }
            $this->xmlRecursive($xml, $key, $sub, $nodeConfig);
        }
        $xml->endElement();

        $buffer = $xml->outputMemory();
        unset($xml);

        return $buffer;
    }

    private function writeAttributes(\XMLWriter $xml, $attributes)
    {
        foreach ($attributes as $name => $value) {
            $xml->writeAttribute($name, $value);
        }
    }

    /**
     * @param \XMLWriter $xml
     * @param $key
     * @param $value
     */
    private function xmlRecursive(\XMLWriter $xml, $key, $value, $subFieldConfig = null)
    {
        if (is_array($value) && isset($value[static::XML_CONFIG_FIELD_MAP])) {
            $key = $value[static::XML_CONFIG_FIELD_MAP];
            unset($value[static::XML_CONFIG_FIELD_MAP]);
        }

        // if key not set and @mapTo not exist use default node name
        if (!is_string($key)) {
            $key = static::DEFAULT_NODE_NAME;
        }

        if ($this->isValueEmpty($value)) {
            return;
        }

        $asSeparateRowKey = false;
        if (!is_object($value) && isset($value[static::XML_CONFIG_FIELD_SEPARATE_ROW])) {
            unset($value[static::XML_CONFIG_FIELD_SEPARATE_ROW]);
            $asSeparateRowKey = true;
        }
        if (!$asSeparateRowKey) {
            $xml->startElement($key);
        }


        if (is_array($value) && isset($value[static::XML_CONFIG_FIELD_ATTR])) {
            if (!$this->isValueEmpty($value[static::XML_CONFIG_FIELD_ATTR])) {
                $this->writeAttributes($xml, $value[static::XML_CONFIG_FIELD_ATTR]);
            }
            unset($value[static::XML_CONFIG_FIELD_ATTR]);
        }

        if (is_array($value) && isset($value[static::XML_CONFIG_FIELD_VALUE])) {
            if (isset($value[static::XML_CONFIG_FIELD_CDATA])) {
                $xml->writeCdata($value[static::XML_CONFIG_FIELD_VALUE]);
            } elseif (!$this->isValueEmpty($value[static::XML_CONFIG_FIELD_VALUE])) {
                $xml->text($value[static::XML_CONFIG_FIELD_VALUE]);
            }
            unset($value[static::XML_CONFIG_FIELD_VALUE]);
            unset($value[static::XML_CONFIG_FIELD_CDATA]);
        }

        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                if ($asSeparateRowKey) {
                    $subKey = $key;
                }
                $this->xmlRecursive($xml, $subKey, $subValue);
            }
        } elseif (!$this->isValueEmpty($value) && !is_object($value) && !is_array($value)) {
            $xml->text($value);
        }
        if (!$asSeparateRowKey) {
            $xml->endElement();
        }
    }

    /**
     * checked if an value (mixed) is empty; diference from php standard function 'empty' is,
     * it allows 0 as NOT empty
     *
     * '' => true
     * ' ' => false
     * '1' => false
     * 'asd' => false
     * 0 => false
     * false => false
     * true => false
     *
     * @param $value
     * @return bool
     */
    public function isValueEmpty($value)
    {
        if ($value === null) {
            return true;
        }

        if (is_object($value)) {
            return false;
        }

        if (is_array($value)) {
            return empty($value);
        }

        return !(isset($value) && strlen((string) $value) > 0);
    }

    /**
     * @param array $configuration
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return (
            $this->targetClassConfigurationValidator->isValid($configuration) &&
            $this->mappingConfigurationValidator->isValid($configuration)
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
        $propertyMappingConfiguration = GeneralUtility::makeInstance(
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
