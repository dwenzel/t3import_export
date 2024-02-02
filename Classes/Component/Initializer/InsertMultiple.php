<?php
namespace CPSIT\T3importExport\Component\Initializer;

/**
 * This file is part of the TYPO3 CMS project.
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Component\AbstractComponent;
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class InsertMultiple
 * Inserts predefined rows into a table
 * @package CPSIT\T3importExport\Component\Initializer
 */
class InsertMultiple extends AbstractComponent implements InitializerInterface
{
    use DatabaseTrait;

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (!(isset($configuration['table']) && is_string($configuration['table']))) {
            return false;
        }
        if (!(isset($configuration['fields']) && is_string($configuration['fields']))) {
            return false;
        }
        if (!(isset($configuration['rows']) && is_array($configuration['rows']))) {
            return false;
        }


        return true;
    }

    /**
     * @param array $configuration
     * @param array $records
     * @return bool
     */
    public function process(array $configuration, array &$records): bool
    {
        $table = $configuration['table'];
        $fields = GeneralUtility::trimExplode(',', $configuration['fields'], true);
        $values = [];
        foreach ($configuration['rows'] as $row) {
            $values[] = GeneralUtility::trimExplode(',', $row, true);
        }
        try {
            $this->connectionPool->getConnectionForTable($table)
                ->bulkInsert($table, $values, $fields);
        } catch (Exception) {
            return false;
        }

        return true;
    }
}
