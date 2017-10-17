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

/**
 * Class Clean
 */
class Clean extends AbstractPreProcessor implements PreProcessorInterface
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
            if (empty($value) || !is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Performs cleaning
     *
     * @param array $configuration
     * @param array $record
     * @return boolean
     */
    public function process($configuration, &$record)
    {
        $fields = $configuration['fields'];
        foreach ($fields as $fieldName => $localConfig) {
            if (
                is_array($localConfig)
                && isset($record[$fieldName])
                && is_string($record[$fieldName])
            ) {

                if (
                    isset($localConfig['str_replace'])
                    && is_array($localConfig['str_replace'])
                    && isset($localConfig['str_replace']['search'])
                    && is_string($localConfig['str_replace']['search'])
                    && isset($localConfig['str_replace']['replace'])
                    && is_string($localConfig['str_replace']['replace'])
                ) {
                    $record[$fieldName] = str_replace($localConfig['str_replace']['search'], $localConfig['str_replace']['replace'], $record[$fieldName]);
                }

                if (isset($localConfig['stripslashes']))
                    $record[$fieldName] = stripslashes($record[$fieldName]);

                if (isset($localConfig['strip_emptytags']))
                    $record[$fieldName] = $this->stripEmptyTags($record[$fieldName]);

                if (isset($localConfig['strip_tags']))
                    $record[$fieldName] = strip_tags($record[$fieldName]);

                if (isset($localConfig['htmlspecialchars']))
                    $record[$fieldName] = htmlspecialchars($record[$fieldName]);

                if (isset($localConfig['strip_spaces']))
                    $record[$fieldName] = preg_replace('/\s+/', '', $record[$fieldName]);

                if (isset($localConfig['trim']))
                    $record[$fieldName] = trim($record[$fieldName]);

                if (isset($localConfig['trim']))
                    $record[$fieldName] = trim($record[$fieldName]);

                if (isset($localConfig['ltrim']))
                    $record[$fieldName] = ltrim($record[$fieldName]);

                if (isset($localConfig['rtrim']))
                    $record[$fieldName] = rtrim($record[$fieldName]);

                if (isset($localConfig['strtolower']))
                    $record[$fieldName] = strtolower($record[$fieldName]);

                if (isset($localConfig['strtoupper']))
                    $record[$fieldName] = strtoupper($record[$fieldName]);

            }
        }

        return true;
    }


    /**
     * Removes or replaces empty tags
     *
     * @param string $string
     * @param string|null $replacement
     * @return string
     */
    private function stripEmptyTags($string, $replacement = null)
    {
        if (!is_string($string) || trim($string) == '') {
            return $string;
        }

        // Recursive empty HTML tags
        return preg_replace(
            '/<(\w+)\b(?:\s+[\w\-.:]+(?:\s*=\s*(?:"[^"]*"|"[^"]*"|[\w\-.:]+))?)*\s*\/?>\s*<\/\1\s*>/',
            !is_string($replacement) ? '' : $replacement,
            $string
        );
    }
}
