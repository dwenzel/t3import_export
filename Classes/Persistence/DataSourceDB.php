<?php
namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\RenderContentInterface;
use CPSIT\T3importExport\RenderContentTrait;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
class DataSourceDB
    implements DataSourceInterface, IdentifiableInterface, RenderContentInterface
{
    use IdentifiableTrait, ConfigurableTrait, RenderContentTrait,
        DatabaseTrait;

    /**
     * Unique identifier of the database connection to use.
     * This connection must be registered with the connection service.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Gets the database connection
     *
     * @return DatabaseConnection
     * @throws \CPSIT\T3importExport\MissingDatabaseException
     */
    public function getDatabase()
    {
        if (!$this->database instanceof DatabaseConnection) {
            $this->database = $this->connectionService->getDatabase($this->identifier);
        }

        return $this->database;
    }

    /**
     * Fetches records from the database
     *
     * @param array $configuration source query configuration
     * @return array Array of records from database or empty array
     */
    public function getRecords(array $configuration)
    {
        $queryConfiguration = [
            'fields' => '*',
            'where' => '',
            'groupBy' => '',
            'orderBy' => '',
            'limit' => ''
        ];

        ArrayUtility::mergeRecursiveWithOverrule(
            $queryConfiguration,
            $configuration,
            true,
            false
        );

        foreach ($queryConfiguration as $key => $value) {
            if (is_array($value)) {
                $renderedValue = $this->renderContent([], $value);
                if (!is_null($renderedValue)) {
                    $queryConfiguration[$key] = $renderedValue;
                }
            }
        }
        $records = $this->getDatabase()->exec_SELECTgetRows(
            $queryConfiguration['fields'],
            $queryConfiguration['table'],
            $queryConfiguration['where'],
            $queryConfiguration['groupBy'],
            $queryConfiguration['orderBy'],
            $queryConfiguration['limit']
        );
        if ($records !== null) {
            return $records;
        }

        return [];
    }

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['table'])
            OR !is_string($configuration['table'])
        ) {
            return false;
        }

        return true;
    }
}
