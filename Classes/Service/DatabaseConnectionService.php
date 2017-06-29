<?php
namespace CPSIT\T3importExport\Service;

use CPSIT\T3importExport\MissingDatabaseException;
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
     * All registered databases
     *
     * @var array<DatabaseConnection>
     */
    protected static $dataBases = [];

    /**
     * Registers a database instance
     * Will silently fail if another database
     * has been registered for this identifier already
     *
     * @param string $identifier
     * @param string $hostName
     * @param string $databaseName
     * @param string $userName
     * @param string $password
     * @param int $port
     */
    public static function register(
        $identifier,
        $hostName = '127.0.0.1',
        $databaseName,
        $userName,
        $password,
        $port = 3306
    ) {
        if (!self::isRegistered($identifier)) {
            /** @var DatabaseConnection $database */
            $database = GeneralUtility::makeInstance(DatabaseConnection::class);

            $database->setDatabaseHost($hostName);
            $database->setDatabaseName($databaseName);
            $database->setDatabaseUsername($userName);
            $database->setDatabasePassword($password);
            $database->setDatabasePort($port);
            $database->initialize();
            self::$dataBases[$identifier] = $database;
        }
    }

    /**
     * Gets a registered database instance by
     * its identifier t
     *
     * @param string $identifier Identifier for the requested database
     * @return DatabaseConnection
     * @throws MissingDatabaseException Thrown if the requested database does not exist
     */
    public function getDatabase($identifier)
    {
        if (self::isRegistered($identifier)) {
            return self::$dataBases[$identifier];
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
    public static function isRegistered($identifier)
    {
        return isset(self::$dataBases[$identifier]);
    }
}
