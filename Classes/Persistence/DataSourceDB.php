<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingDatabaseException;
use CPSIT\T3importExport\Persistence\Query\QueryFacade;
use CPSIT\T3importExport\Persistence\Query\SelectQuery;
use CPSIT\T3importExport\RenderContentInterface;
use CPSIT\T3importExport\RenderContentTrait;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

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
class DataSourceDB implements DataSourceInterface, ConfigurableInterface, IdentifiableInterface, RenderContentInterface
{
    use IdentifiableTrait;
    use ConfigurableTrait;
    use RenderContentTrait;
    use DatabaseTrait;

    /**
     * Unique identifier of the database connection to use.
     * This connection must be registered with the connection service.
     */
    protected ?string $identifier = null;

    /**
     * Gets the database connection
     *
     * @return Connection
     * @throws MissingDatabaseException|DBALException
     */
    public function getDatabase()
    {
        if (
            !$this->database instanceof Connection
            || (
                !empty($this->identifier) && $this->database === $GLOBALS['TYPO3_DB']
            )
        ) {
            $this->database = $this->connectionService->getDatabase($this->identifier);
        }

        return $this->database;
    }

    /**
     * Fetches records from the database
     *
     * @param array $configuration source query configuration
     * @return array Array of records from database or empty array
     * @throws InvalidConfigurationException
     * @throws ContentRenderingException
     */
    public function getRecords(array $configuration): array
    {
        $records = [];
        if (!$this->isConfigurationValid($configuration)) {
            throw new InvalidConfigurationException();
        }

        $queryConfiguration = $this->renderValues($configuration);

        try {
            /** @var SelectQuery $query */
            $query = GeneralUtility::makeInstance(SelectQuery::class);
            if ($this->identifier) {
                $query = $query->withDatabaseIdentifier($this->identifier);
            }

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $query->withConfiguration($queryConfiguration)
                ->setQuery()
                ->build();

            $records = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception) {
            // todo: log error
        }

        return $records;
    }

    /**
     * Tells if a given configuration is valid
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return !(!isset($configuration['table'])
            || !is_string($configuration['table']));
    }

    /**
     * @throws ContentRenderingException
     */
    protected function renderValues(array $queryConfiguration): array
    {
        foreach ($queryConfiguration as $key => $value) {
            if (is_array($value) && isset($value['_typoScriptNodeValue'])) {
                $renderedValue = $this->renderContent([], $value);
                if (!is_null($renderedValue)) {
                    $queryConfiguration[$key] = $renderedValue;
                }
            }
        }
        return $queryConfiguration;
    }
}
