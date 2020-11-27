<?php

namespace CPSIT\T3importExport\Service;

use CPSIT\T3importExport\MissingDatabaseException;
use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
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

    public const MISSING_CONNECTION_ERROR = 'Connection with identifier "%s" not found.';

    /**
     * @var ConnectionPool
     */
    protected static $connectionPool;

    public function __construct(ConnectionPool $connectionPool = null)
    {
        self::$connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Registers a database connection
     *
     * Will silently fail if another connection
     * has been registered for this identifier already
     * For valid parameters @see ConnectionPool::getDatabaseConnection()
     *
     * @param string $identifier
     * @param array $connectionParameters
     */
    public static function register(string $identifier, array $connectionParameters): void
    {
        if (!self::isRegistered($identifier)) {
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$identifier] = $connectionParameters;
        }
    }

    /**
     * Tells if a database has been registered for
     * the given identifier
     *
     * @param string $identifier
     * @return bool
     */
    public static function isRegistered($identifier): bool
    {
        $connections = self::getConnectionPool()->getConnectionNames();
        return (!empty($connections)
            && in_array($identifier, $connections, true)
        );
    }

    /**
     * @return ConnectionPool
     */
    public static function getConnectionPool(): ConnectionPool
    {
        if (!self::$connectionPool instanceof ConnectionPool) {
            self::$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }

        return self::$connectionPool;
    }

    /**
     * Gets a registered database instance by identifier
     *
     * @param string $identifier Identifier for the requested database
     * @return Connection
     * @throws DBALException
     */
    public function getDatabase(string $identifier): Connection
    {
        try {
            return self::getConnectionPool()->getConnectionByName($identifier);
        }
        catch (\Exception $exception) {
            $message = sprintf(self::MISSING_CONNECTION_ERROR, $identifier);

            throw new MissingDatabaseException(
                $message,
                1606403608
            );
        }
    }
}
