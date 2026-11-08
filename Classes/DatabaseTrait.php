<?php
namespace CPSIT\T3importExport;

use CPSIT\T3importExport\Service\DatabaseConnectionService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareInterface;
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
     * Database
     *
     * @var Connection
     * @deprecated
     */
    protected ?Connection $database = null;

    /**
     * Constructor
     * @param ConnectionPool|null $connectionPool
     * @param DatabaseConnectionService|null $connectionService
     */
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected DatabaseConnectionService $connectionService
    ) {
    }

    /**
     * @return Connection
     * @deprecated
     * use @see ConnectionPool->getConnectionForTable() instead
     */
    public function getDataBase(): Connection
    {
        return $this->database ??= $this->connectionPool->getConnectionByName('Default');
    }

    public function getDatabaseConnectionService(): DatabaseConnectionService
    {
        return $this->connectionService;
    }
}
