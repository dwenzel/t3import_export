<?php
namespace CPSIT\T3importExport\Service;

use CPSIT\T3importExport\MissingDatabaseException;
use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Class DatabaseConnectionService
 * Registers database connections and makes them
 * available by identifier
 *
 * @package CPSIT\T3importExport\Service
 */
class DatabaseConnectionService implements SingletonInterface
{

    /**
     * @var ConnectionPool
     */
    protected ConnectionPool $connectionPool;

    public function __construct($connectionPool = null)
    {
        $this->connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }
    /**
     * Gets a registered database instance by
     * its identifier
     *
     * @param string $identifier Identifier for the requested database
     * @return Connection
     * @throws MissingDatabaseException|DBALException Thrown
     * if the requested database does not exist @see
     * @deprecated
     * Use @see ConnectionPool::getConnectionForTable() instead
     */
    public function getDatabase($identifier): Connection
    {
        if ($this->isRegistered($identifier)) {
            return $this->getConnectionPool()->getConnectionByName($identifier);
        }
        throw new MissingDatabaseException(
            'No database registered for identifier ' . $identifier,
            1449363030
        );
    }

    /**
     * Tells if a database has been registered for
     * the given identifier
     *
     * @param string $identifier
     * @return bool
     */
    public function isRegistered($identifier): bool
    {
        $connections = $this->connectionPool->getConnectionNames();
        return in_array($identifier, $connections);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return $this->connectionPool;
    }
}
