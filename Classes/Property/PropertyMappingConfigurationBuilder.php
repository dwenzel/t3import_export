<?php

namespace CPSIT\T3importExport\Property;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

//use TYPO3\CMS\Extbase\Utility\ArrayUtility;

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
class PropertyMappingConfigurationBuilder
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * injects the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $configuration
     * @return PropertyMappingConfiguration
     */
    public function build(array $configuration)
    {
        /** @var PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = GeneralUtility::makeInstance(
            PropertyMappingConfiguration::class
        );
        $this->configure($configuration, $propertyMappingConfiguration);

        return $propertyMappingConfiguration;
    }

    /**
     * @param array $configuration
     * @param PropertyMappingConfiguration $propertyMappingConfiguration
     */
    protected function configure(array $configuration, PropertyMappingConfiguration $propertyMappingConfiguration)
    {
        $propertyMappingConfiguration->setTypeConverterOptions(
            $this->getTypeConverterClass($configuration),
            $this->getTypeConverterOptions($configuration)
        );

        $propertyMappingConfiguration->skipUnknownProperties();
        if ($this->getAllowAllProperties($configuration)) {
            $propertyMappingConfiguration->allowAllProperties();
        } else {
            $allowedProperties = $this->getAllowedProperties($configuration);
            if ((bool)$allowedProperties) {
                call_user_func_array(
                    [$propertyMappingConfiguration, 'allowProperties'],
                    $allowedProperties
                );
            }
        }
        if ((bool)$properties = $this->getProperties($configuration)) {
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
                    && isset($localConfiguration['children']['maxItems']))
                ) {
                    continue;
                }
                $maxItems = (int)$localConfiguration['children']['maxItems'];
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
     * Gets the type converter class
     * Default class name is returned if not set in configuration
     *
     * @param $configuration
     * @return string
     */
    protected function getTypeConverterClass($configuration)
    {
        if (isset($configuration['typeConverter']['class'])
            && is_string($configuration['typeConverter']['class'])
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
    protected function getTypeConverterOptions($configuration)
    {
        $options = [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ];

        if (isset($configuration['typeConverter']['options'])
            && is_array($configuration['typeConverter']['options'])
        ) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $options,
                $configuration['typeConverter']['options']
            );
        }

        return $options;
    }

    /**
     * Tells if all properties should be allowed to map
     *
     * @param $configuration
     * @return bool
     */
    protected function getAllowAllProperties($configuration)
    {
        if (isset($configuration['allowAllProperties'])
            && (bool)$configuration['allowAllProperties']
        ) {
            return true;
        }

        return false;
    }

    /**
     * Gets the allowed properties from configuration
     * An empty array is returned if not set
     *
     * @param $configuration
     * @return array
     */
    protected function getAllowedProperties($configuration)
    {
        if (isset($configuration['allowProperties'])
            && is_string($configuration['allowProperties'])
        ) {
            $allowedProperties = explode(
                ',',
                trim($configuration['allowProperties'])
            );

            return $allowedProperties;
        }

        return [];
    }

    /**
     * @param $configuration
     * @return array
     */
    public function getProperties($configuration)
    {
        $properties = [];
        if (isset($configuration['properties'])
            && is_array($configuration['properties'])
        ) {
            $properties = $configuration['properties'];
        }

        return $properties;
    }
}
