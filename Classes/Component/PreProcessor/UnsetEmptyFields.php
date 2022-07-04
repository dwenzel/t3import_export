<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

/**
 * This file is part of the "Import Export" project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UnsetEmptyFields
 */
class UnsetEmptyFields extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * Tells whether a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return !(empty($configuration)
            || !isset($configuration['fields'])
            || empty($configuration['fields']));
    }

    /**
     * Processes the incoming record according to configuration
     * Unsets any field which is configured in configuration and empty in record
     *
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $fieldNames = GeneralUtility::trimExplode(',', $configuration['fields'], true);
        foreach ($fieldNames as $fieldName) {
            if (isset($record[$fieldName]) && empty($record[$fieldName]) || is_null($record[$fieldName])) {
                unset($record[$fieldName]);
            }
        }

        return true;
    }
}
