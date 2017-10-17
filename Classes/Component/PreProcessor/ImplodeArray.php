<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

/***************************************************************
 *  Copyright notice
 *  (c) 2017 Jan-Henrik Hempel <hempel@motor.berlin>
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
class ImplodeArray extends AbstractPreProcessor implements PreProcessorInterface
{

    /**
     * @var array
     */
    protected $fields = [];

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
            if (empty($value)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param array $record
     * @return TRUE
     */
    public function process($configuration, &$record)
    {
        $fields = $configuration['fields'];
        foreach ($fields as $fieldName => $localConfig) {
            if (is_array($localConfig)) {
                if (isset($localConfig['child']) && is_string($localConfig['child'])) {
                    if (isset($record[$fieldName]) && isset($record[$fieldName][$localConfig['child']])) {
                        if (is_string($record[$fieldName][$localConfig['child']])) {
                            $record[$fieldName] = $record[$fieldName][$localConfig['child']];
                        } elseif (is_array($record[$fieldName][$localConfig['child']])) {
                            if (isset($localConfig['wrap'])) {
                                $record[$fieldName] = implode($localConfig['wrap'], $record[$fieldName][$localConfig['child']]);
                            } else {
                                $record[$fieldName] = implode(',', $record[$fieldName][$localConfig['child']]);
                            }
                        }
                    }
                } else {
                    if (isset($record[$fieldName])) {
                        if (is_array($record[$fieldName])) {
                            if (isset($localConfig['wrap'])) {
                                $record[$fieldName] = implode($localConfig['wrap'], $record[$fieldName]);
                            } else {
                                $record[$fieldName] = implode(',', $record[$fieldName]);
                            }
                        }
                    }
                }
            }
        }
    }
}
