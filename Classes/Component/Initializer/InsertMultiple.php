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
    public function isConfigurationValid(array $configuration)
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
        if (isset($configuration['identifier']) && !is_string($configuration['identifier'])) {
            return false;
        }
        if (isset($configuration['identifier'])
            && !DatabaseConnectionService::isRegistered($configuration['identifier'])
        ) {
            return false;
        }


        return true;
    }

    /**
     * @param array $configuration
     * @param array $records
     * @return bool
     */
    public function process($configuration, &$records)
    {
        if (isset($configuration['identifier'])) {
            $this->database = $this->connectionService
                ->getDatabase($configuration['identifier']);
        }
        $table = $configuration['table'];
        $fields = GeneralUtility::trimExplode(',', $configuration['fields'], true);
        $values = [];
        foreach ($configuration['rows'] as $row) {
            $values[] = GeneralUtility::trimExplode(',', $row, true);
        }

        return (bool) $this->database->exec_INSERTmultipleRows($table, $fields, $values);
    }
}
