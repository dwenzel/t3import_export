<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

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
/**
 * Class MapFieldValues
 * Maps matching values in incoming record to new values from
 * configuration
 *
 * @package CPSIT\T3importExport\Component\PreProcessor
 */
class MapFieldValues
    extends AbstractPreProcessor
    implements PreProcessorInterface
{

    /**
     * Tells whether the configuration is valid
     * $configuration['fields'] must be an array with keys indicating
     * field names in record.
     * Each field configuration must contain a targetField and
     * an a field 'values' which holds an array of <oldValue> = <newValue> pairs.
     * Example:
     * config {
     *  fields {
     *   fooField {
     *     targetField = barField
     *     values {
     *      barValue = bazValue
     *     }
     *    }
     *   }
     *  }
     * Value of barField will be set to bazValue if fooField
     * contains bazValue.
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['fields'])) {
            return false;
        }
        if (isset($configuration['fields'])
            AND !is_array($configuration['fields'])
        ) {
            return false;
        }
        foreach ($configuration['fields'] as $field) {
            if (!isset($field['targetField'])
                OR !is_string(($field['targetField']))
            ) {
                return false;
            }
            if (!isset($field['values'])
                OR !is_array($field['values'])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Replaces matching field values by values from
     * configuration
     *
     * @param array $configuration
     * @param array $record
     * @return TRUE
     */
    public function process($configuration, &$record)
    {
        $fields = $configuration['fields'];
        foreach ($fields as $fieldName => $localConfig) {
            $this->mapValues($fieldName, $localConfig, $record);
        }

        return true;
    }

    /**
     * Maps values for a single field
     *
     * @param string $fieldName
     * @param array $config
     * @param array $record
     */
    protected function mapValues($fieldName, $config, &$record)
    {
        foreach ($config['values'] as $sourceValue => $targetValue) {
            if ($record[$fieldName] == $sourceValue) {
                $record[$config['targetField']] = $targetValue;
            }
        }
    }
}
