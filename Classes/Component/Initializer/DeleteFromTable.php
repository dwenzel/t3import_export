<?php
namespace CPSIT\T3importExport\Component\Initializer;

/**
 * This file is part of the TYPO3 CMS project.
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

use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Service\DatabaseConnectionService;

/**
 * Class DeleteFromTable
 * Deletes records from a given table either from default database or a
 * database registered with DatabaseConnectionService by identifier.
 * Records to delete are determined by a where clause
 * @package \CPSIT\T3importExport\Component\Initializer
 */
class DeleteFromTable
    extends AbstractInitializer
    implements InitializerInterface
{
    use DatabaseTrait;

    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @return bool
     */
    public function process($configuration, &$records)
    {
        if (isset($configuration['identifier'])) {
            $this->database = $this->connectionService
                ->getDatabase($configuration['identifier']);
        }
        $table = $configuration['table'];
        $where = $configuration['where'];

        return (bool)$this->database->exec_DELETEquery($table, $where);
    }

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['table'])
            || !is_string($configuration['table'])
        ) {
            return false;
        }

        if (!isset($configuration['where'])
            || !is_string($configuration['where'])
        ) {
            return false;
        }

        if (isset($configuration['identifier'])
            AND !DatabaseConnectionService::isRegistered($configuration['identifier'])
        ) {
            return false;
        }

        return true;
    }
}
