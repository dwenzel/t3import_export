<?php

declare(strict_types=1);

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

use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UnsetEmptyFields
 *
 * @deprecated since 3.2, will be removed in 4.0. Use \CPSIT\ImportExportCore\Processor\UnsetEmptyFieldsPreProcessor of cpsit/import-export-core 0.2, see Documentation/DEPRECATIONS.md
 */
class UnsetEmptyFields extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * Tells whether a given configuration is valid
     */
    #[\Override]
    public function isConfigurationValid(array $configuration): bool
    {
        return !(empty($configuration)
            || !isset($configuration['fields'])
            || empty($configuration['fields']));
    }

    /**
     * Processes the incoming record according to configuration
     * Unsets any field that is configured in configuration and empty in record
     *
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function process(array $configuration, array &$record): bool
    {
        $fieldNames = GeneralUtility::trimExplode(',', $configuration['fields'], true);
        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $record) && empty($record[$fieldName])) {
                unset($record[$fieldName]);
            }
        }

        return true;
    }
}
