<?php
namespace CPSIT\T3importExport;

use CPSIT\T3importExport\Service\DatabaseConnectionService;
use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Database\DatabaseConnection;

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
 * Class DatabaseTrait
 * Provides a database connection service and a
 * database. The database property is set to the
 * default TYPO DB on instantiation.
 *
 * @package CPSIT\T3importExport
 */
trait DatabaseTrait
{
    /**
     * Database connection service
     *
     * @var \CPSIT\T3importExport\Service\DatabaseConnectionService
     */
    protected $connectionService;

    /**
     * Database
     *
     * @var Connection
     */
    protected $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!$this->database instanceof Connection) {
            $this->database = $GLOBALS['TYPO3_DB'];
        }
    }

    public function getDataBase(): Connection
    {
        return $this->database;
    }

    public function getDatabaseConnectionService(): DatabaseConnectionService
    {
        return $this->connectionService;
    }

    /**
     * Injects the database connection service
     *
     * @param \CPSIT\T3importExport\Service\DatabaseConnectionService $dbConnectionService
     */
    public function injectDatabaseConnectionService(DatabaseConnectionService $dbConnectionService)
    {
        $this->connectionService = $dbConnectionService;
    }
}
