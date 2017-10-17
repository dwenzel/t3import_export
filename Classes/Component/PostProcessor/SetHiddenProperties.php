<?php
namespace CPSIT\T3importExport\Component\PostProcessor;

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
use CPSIT\T3importExport\Component\PostProcessor\AbstractPostProcessor;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;

/**
 * Class SetHiddenProperties
 * Sets fields of AbstractDomainObject instances
 * which are not settable via PropertyMapper.
 * I.e. _languageUid, _localizedUid, $_versionedUid
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class SetHiddenProperties extends AbstractPostProcessor implements \CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface
{

    /**
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['fields'])) {
            return false;
        }
        if (!is_array($configuration['fields'])) {
            return false;
        }
        foreach ($configuration['fields'] as $field => $value) {
            if (!is_string($field)
                || empty($value)
            ) {
                return false;
            }
        }
        if (isset($configuration['children'])
            && !is_array($configuration['children'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param AbstractDomainObject $convertedRecord
     * @param array $record
     * @return TRUE
     */
    public function process($configuration, &$convertedRecord, &$record)
    {
        $fields = $configuration['fields'];
        foreach ($fields as $fieldName => $localConfiguration) {
            $value = $record[$fieldName];
            $this->setHiddenProperty(
                $configuration,
                $convertedRecord,
                $fieldName,
                $value
            );
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param AbstractDomainObject $convertedRecord
     * @param string $fieldName
     * @param $value
     */
    protected function setHiddenProperty($configuration, &$convertedRecord, $fieldName, $value)
    {
        $convertedRecord->_setProperty('_' . $fieldName, $value);
        if (isset($configuration['children'])) {
            $localConf = $configuration['children'];
            $this->setPropertiesRecursive($convertedRecord, $fieldName, $value, $localConf);
        }
    }

    /**
     * @param AbstractDomainObject $convertedRecord
     * @param string $fieldName
     * @param $value
     * @param array $configuration
     */
    protected function setPropertiesRecursive(&$convertedRecord, $fieldName, $value, $configuration)
    {
        foreach ($configuration as $propertyName => $childConfig) {
            if ($convertedRecord->_hasProperty($propertyName)) {
                $propertyValue = $convertedRecord->_getProperty($propertyName);

                if ($propertyValue instanceof ObjectStorage) {
                    /** ObjectStorage $propertyValue */
                    foreach ($propertyValue as $child) {
                        $this->setHiddenProperty($childConfig, $child, $fieldName, $value);
                    }
                }
                if ($propertyValue instanceof AbstractDomainObject) {
                    $propertyValue->_setProperty('_' . $fieldName, $value);
                }
            }
        }
    }
}
