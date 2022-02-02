<?php

namespace CPSIT\T3importExport\Component\Initializer;

use CPSIT\T3importExport\ConfigurableTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class TruncateTables
 * truncates given tables either from default database or a
 * database registered with DatabaseConnectionService by identifier
 *
 * @package CPSIT\T3importExport\Component\Initializer
 */
class TruncateTables extends AbstractInitializer implements InitializerInterface
{
    use ConfigurableTrait;
    protected ConnectionPool $connectionPool;

    public const KEY_TABLES = 'tables';
    public const DELIMITER = ',';

    /**
     * Constructor
     * @param ConnectionPool|null $connectionPool
     */
    public function __construct(ConnectionPool $connectionPool = null)
    {
        $this->connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @return bool
     */
    public function process(array $configuration, array &$records): bool
    {
        if (isset($configuration['tables'])) {
            $tables = GeneralUtility::trimExplode(
                ',',
                $configuration['tables'],
                true
            );
            if ((bool)$tables) {
                foreach ($tables as $table) {
                    $this->connectionPool->getConnectionForTable($table)
                        ->truncate($table);
                }
            }
        }

        return true;
    }

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return (!empty($configuration['tables'])
            && is_string($configuration['tables']));
    }
}
