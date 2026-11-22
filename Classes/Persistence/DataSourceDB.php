<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\Dbal\DataSourceDBAL;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\IdentifiableInterface;
use CPSIT\ImportExportCore\IdentifiableTrait;
use CPSIT\T3importExport\Dbal\DataSourceDBConfiguration;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use CPSIT\T3importExport\Exception\MissingDatabaseException;
use CPSIT\T3importExport\Expression\ExpressionEvaluatorTrait;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Doctrine\DBAL\Driver\Exception as DriverException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
 * Reads records from a database table.
 *
 * Adapter on `DataSourceDBAL` of the core, which accepts the options of
 * earlier versions:
 *
 * - `fields` and `orderBy` as comma-separated strings and `limit` as string
 *   are converted into the options of the core
 * - SQL in the options (a `where` string, `join`, `groupBy`, `alias`,
 *   expressions or qualified names in `fields` or `orderBy`) is read by a
 *   fallback with the query builder of TYPO3, as before 4.0 (deprecated,
 *   removed in 5.0)
 * - values given as `{expression: ...}` are evaluated first
 * - without `connection`, the connection of the table mapping of TYPO3 is
 *   used; `identifier` of the component selects a connection by name
 *   (deprecated)
 *
 * Neither the core nor the fallback apply the restrictions of TYPO3
 * (`deleted`, `hidden`, start and end time), as before 4.0.
 *
 * `getRecords()` returns an array, as before 4.0. With `cursorField` it
 * returns the generator of the core, so that an interrupted task can continue
 * after the last processed record.
 */
class DataSourceDB extends DataSourceDBAL implements IdentifiableInterface
{
    use IdentifiableTrait;
    use ExpressionEvaluatorTrait;

    final public const int CONTENT_OBJECT_NOT_SUPPORTED_EXCEPTION_CODE = 1_794_781_881;
    final public const int INVALID_CONFIGURATION_EXCEPTION_CODE = 1_796_500_001;
    final public const int MISSING_DATABASE_EXCEPTION_CODE = 1_449_363_030;

    public function __construct(
        protected readonly Typo3ConnectionProvider $typo3ConnectionProvider,
        ?\Closure $clock = null,
    ) {
        parent::__construct($typo3ConnectionProvider, $clock);
    }

    /**
     * Tells if a given configuration is valid
     *
     * A content object or an expression that cannot be evaluated is reported
     * by `getRecords()`, as before 4.0.
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (!isset($configuration[self::KEY_TABLE]) || !\is_string($configuration[self::KEY_TABLE])) {
            return false;
        }
        try {
            $configuration = $this->renderValues($configuration);
        } catch (\Throwable) {
            return true;
        }
        if (DataSourceDBConfiguration::fallbackOptions($configuration) === []) {
            return parent::isConfigurationValid(DataSourceDBConfiguration::toCore($configuration));
        }

        return $this->isFallbackConfigurationValid($configuration);
    }

    /**
     * Fetches records from the database
     *
     * @param array $configuration source query configuration
     * @return iterable<array<string, mixed>> an array of records; the generator of the core with `cursorField`
     * @throws InvalidConfigurationException
     * @throws MissingDatabaseException
     */
    public function getRecords(array $configuration): iterable
    {
        if (!isset($configuration[self::KEY_TABLE]) || !\is_string($configuration[self::KEY_TABLE])) {
            throw new InvalidConfigurationException(
                'Option "table" of DataSourceDB must be a string.',
                self::INVALID_CONFIGURATION_EXCEPTION_CODE
            );
        }
        $configuration = $this->renderValues($configuration);
        $fallbackOptions = DataSourceDBConfiguration::fallbackOptions($configuration);
        if ($fallbackOptions !== []) {
            return $this->getRecordsWithFallback($configuration, $fallbackOptions);
        }

        $configuration = DataSourceDBConfiguration::toCore($configuration);
        if (!parent::isConfigurationValid($configuration)) {
            throw new InvalidConfigurationException(
                'Invalid configuration of DataSourceDB.',
                self::INVALID_CONFIGURATION_EXCEPTION_CODE
            );
        }
        $configuration[self::KEY_CONNECTION] = $this->getConnectionName($configuration);
        if (isset($configuration[self::KEY_CURSOR_FIELD])) {
            return parent::getRecords($configuration);
        }

        try {
            return iterator_to_array(parent::getRecords($configuration), false);
        } catch (DriverException) {
            // as before 4.0: a failing query returns no records
            return [];
        }
    }

    /**
     * Returns the name of the connection: `connection` of the configuration,
     * the deprecated `identifier` of the component or the connection of the
     * table mapping of TYPO3.
     *
     * @param array<array-key, mixed> $configuration
     * @throws MissingDatabaseException if `identifier` names a connection that is not configured
     */
    protected function getConnectionName(array $configuration): string
    {
        $connection = $configuration[self::KEY_CONNECTION] ?? null;
        if (\is_string($connection) && $connection !== '') {
            return $connection;
        }
        $identifier = $this->identifier;
        if ($identifier !== null && $identifier !== '' && $identifier !== ConnectionPool::DEFAULT_CONNECTION_NAME) {
            DeprecationUtility::trigger(
                'Selecting the database connection of DataSourceDB with "identifier" is deprecated since 4.0 and will be removed in 5.0. Use the option "connection".',
                DeprecationUtility::DATABASE_ISSUE_URL
            );
            if (!$this->typo3ConnectionProvider->hasConnection($identifier)) {
                throw new MissingDatabaseException(
                    'No database registered for identifier ' . $identifier,
                    self::MISSING_DATABASE_EXCEPTION_CODE
                );
            }

            return $identifier;
        }

        return $this->typo3ConnectionProvider->getConnectionNameForTable((string)$configuration[self::KEY_TABLE]);
    }

    /**
     * Reads the records with the query builder of TYPO3, as before 4.0. SQL
     * from the configuration is used as it is; the configuration must be
     * trusted.
     *
     * @deprecated since 4.0, will be removed in 5.0
     * @param array<array-key, mixed> $configuration
     * @param list<string> $fallbackOptions
     * @return list<array<string, mixed>>
     */
    protected function getRecordsWithFallback(array $configuration, array $fallbackOptions): array
    {
        if (!$this->isFallbackConfigurationValid($configuration)) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The options %s of DataSourceDB cannot be combined with "where" as map, "%s" or "connection" other than a string.',
                    '"' . implode('", "', $fallbackOptions) . '"',
                    implode('", "', DataSourceDBConfiguration::CORE_ONLY_KEYS)
                ),
                self::INVALID_CONFIGURATION_EXCEPTION_CODE
            );
        }
        DeprecationUtility::trigger(
            sprintf(
                'SQL in the options %s of DataSourceDB is deprecated since 4.0 and will not be accepted in 5.0. Use the options "fields", "where", "filter" and "orderBy" of the core (DataSourceDBAL).',
                '"' . implode('", "', $fallbackOptions) . '"'
            ),
            DeprecationUtility::DATABASE_ISSUE_URL
        );
        $connection = $this->typo3ConnectionProvider->getConnection($this->getConnectionName($configuration));

        try {
            return $this->createFallbackQuery($connection, $configuration)
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (DriverException) {
            // as before 4.0: a failing query returns no records
            return [];
        }
    }

    /**
     * Builds the query as `Persistence\Query\SelectQuery` did before 4.0,
     * without the restrictions of TYPO3.
     *
     * @param array<array-key, mixed> $configuration
     */
    protected function createFallbackQuery(Connection $connection, array $configuration): QueryBuilder
    {
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $table = (string)$configuration[self::KEY_TABLE];
        $alias = !empty($configuration[DataSourceDBConfiguration::KEY_ALIAS])
            ? (string)$configuration[DataSourceDBConfiguration::KEY_ALIAS]
            : $table;
        $fields = $configuration[self::KEY_FIELDS] ?? null;
        if (\is_array($fields)) {
            $fields = $fields === [] ? '*' : implode(',', $fields);
        }
        $fields = empty($fields) ? '*' : (string)$fields;
        $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
            ->from($table, $alias);

        if (DataSourceDBConfiguration::hasJoin($configuration)) {
            /** @var array<string, mixed> $join */
            $join = $configuration[DataSourceDBConfiguration::KEY_JOIN];
            $joinTable = (string)$join[self::KEY_TABLE];
            $queryBuilder->join(
                $alias,
                $joinTable,
                (string)($join[DataSourceDBConfiguration::KEY_ALIAS] ?? $joinTable),
                isset($join[DataSourceDBConfiguration::KEY_JOIN_CONDITION]) ? (string)$join[DataSourceDBConfiguration::KEY_JOIN_CONDITION] : null
            );
        }
        if (!empty($configuration[self::KEY_WHERE])) {
            $queryBuilder->where((string)$configuration[self::KEY_WHERE]);
        }
        if (!empty($configuration[DataSourceDBConfiguration::KEY_GROUP_BY])) {
            $queryBuilder->groupBy((string)$configuration[DataSourceDBConfiguration::KEY_GROUP_BY]);
        }
        $orderBy = $configuration[self::KEY_ORDER_BY] ?? null;
        if (\is_array($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy((string)$field, strtolower((string)$direction) === 'desc' ? 'DESC' : 'ASC');
            }
        } elseif (!empty($orderBy)) {
            foreach (GeneralUtility::trimExplode(',', (string)$orderBy, true) as $item) {
                $words = GeneralUtility::trimExplode(' ', $item, true);
                $queryBuilder->addOrderBy($words[0], isset($words[1]) && strtolower($words[1]) === 'desc' ? 'DESC' : 'ASC');
            }
        }
        if (!empty($configuration[self::KEY_LIMIT])) {
            $queryBuilder->setMaxResults((int)$configuration[self::KEY_LIMIT]);
        }

        return $queryBuilder;
    }

    /**
     * The fallback accepts the options of earlier versions, but not the
     * options of the core that it cannot apply.
     *
     * @param array<array-key, mixed> $configuration
     */
    protected function isFallbackConfigurationValid(array $configuration): bool
    {
        foreach (DataSourceDBConfiguration::CORE_ONLY_KEYS as $key) {
            if (\array_key_exists($key, $configuration)) {
                return false;
            }
        }
        if (\is_array($configuration[self::KEY_WHERE] ?? null) && $configuration[self::KEY_WHERE] !== []) {
            return false;
        }
        $connection = $configuration[self::KEY_CONNECTION] ?? null;

        return $connection === null || (\is_string($connection) && $connection !== '');
    }

    /**
     * Evaluates `{expression: ...}` values
     *
     * @param array<array-key, mixed> $queryConfiguration
     * @return array<array-key, mixed>
     * @throws InvalidConfigurationException for content objects, which are not supported any more
     */
    protected function renderValues(array $queryConfiguration): array
    {
        foreach ($queryConfiguration as $key => $value) {
            if (is_array($value) && isset($value['expression'])) {
                $queryConfiguration[$key] = $this->getExpressionEvaluator()->evaluate((string)$value['expression']);
                continue;
            }
            if (is_array($value) && isset($value['_typoScriptNodeValue'])) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'Query value "%s" of DataSourceDB is a TypoScript content object, which is not supported any more. Use {expression: ...} instead.',
                        $key
                    ),
                    self::CONTENT_OBJECT_NOT_SUPPORTED_EXCEPTION_CODE
                );
            }
        }
        return $queryConfiguration;
    }
}
