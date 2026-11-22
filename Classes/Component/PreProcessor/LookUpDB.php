<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Persistence\Query\QueryFacade;
use CPSIT\T3importExport\Persistence\Query\SelectJoinQuery;
use CPSIT\T3importExport\Persistence\Query\SelectQuery;

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
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class LookUpDB
 * Base class for database look up.
 * Children must implement PreProcessorInterface
 *
 * Configuration @see SelectQuery, SelectJoinQuery
 */
class LookUpDB extends AbstractPreProcessor implements PreProcessorInterface
{
    use DatabaseTrait;

    /* name mixing:
        'select' stands for every sql query
        could be 'sql' or 'query' for INSERT and others, but would change configuration api

        ...still use *

        80 {
            class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
            config {
                targetField = foo
         *----> select {   <-----*
                    type = updateSelect
                    table = tx_foo
                    ...
                }
            }
         }

    */
    protected const CONFIGURATION_KEY_QUERY = 'select';

    /**
     * Alias of the column used to sort the result with `keepOrder`
     */
    protected const ORDER_FIELD_ALIAS = 'tx_t3importexport_order';

    /**
     * Tells if a given configuration is valid
     */
    #[\Override]
    public function isConfigurationValid(array $configuration): bool
    {
        if (!isset($configuration['select'])
            || !is_array($configuration['select'])
        ) {
            return false;
        }
        if (!isset($configuration['select']['table'])
            || !is_string(($configuration['select']['table']))
        ) {
            return false;
        }
        if (!isset($configuration['targetField']) || !is_string($configuration['targetField'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param array $record
     *
     * @throws InvalidConfigurationException
     */
    public function process(array $configuration, array &$record): bool
    {
        /**
         * todo method cannot be tested because of its complexity and
         * the dependency from  SelectQuery
         * We probably should extract
         * - a parser for the configuration which produces the configuration for
         *   the query and / or
         * - a builder class or factory for the query
         */
        if (isset($configuration['childRecords'])
            && is_array($record[$configuration['childRecords']])
        ) {
            $localConfiguration = $configuration;
            unset($localConfiguration['childRecords']);
            foreach ($record[$configuration['childRecords']] as &$childRecord) {
                $this->process($localConfiguration, $childRecord);
            }
            return true;
        }
        $queryConfiguration = $this->getQueryConfiguration($configuration);
        $constraints = is_array($queryConfiguration['where'] ?? null) ? $queryConfiguration['where'] : [];
        try {
            $inValues = $this->getInConstraintValues($record, $queryConfiguration);
            $queryConfiguration = $this->parseQueryConstraints($record, $queryConfiguration, $inValues);
        } catch (InvalidConfigurationException) {
            // todo: log error
            return false;
        }

        $queryResult = [];
        if (!$this->isAlwaysEmpty($constraints, $inValues)) {
            $keepOrder = $inValues !== null && !empty($constraints['IN']['keepOrder']);
            $queryResult = $keepOrder
                ? $this->findRowsInOrder($queryConfiguration, $constraints['IN']['field'], $inValues)
                : $this->findRows($queryConfiguration);
        }

        $targetField = $configuration['targetField'];

        if (empty($queryResult)) {
            unset($record[$targetField]);
            return true;
        }

        if (!empty($queryConfiguration['singleRow'])) {
            // consider only first result
            $this->mapFields($record, $queryResult[0], $configuration);
        } else {
            $mappedRecords = [];
            foreach ($queryResult as $row) {
                $mappedRecord = [];
                $this->mapFields($mappedRecord, $row, $configuration);
                $mappedRecords[] = $mappedRecord;
            }
            $record[$targetField] = $mappedRecords;
        }

        return true;
    }

    /**
     * Runs the query
     */
    protected function findRows(array $queryConfiguration): array
    {
        if (!empty($queryConfiguration['singleRow'])) {
            $queryConfiguration['limit'] = 1;
        }

        return (new QueryFacade())->getQueryResultByConfig($queryConfiguration);
    }

    /**
     * Runs the query and sorts the rows by the order of the values
     * of the IN constraint. Sorting in PHP works on all databases.
     * With groupBy the rows are not sorted: the additional column for
     * sorting could break the grouping.
     *
     * @param string[] $values
     */
    protected function findRowsInOrder(array $queryConfiguration, string $field, array $values): array
    {
        if (!empty($queryConfiguration['groupBy'])) {
            return $this->findRows($queryConfiguration);
        }
        // the rows within the limit are only known after sorting
        $limit = (int)($queryConfiguration['limit'] ?? 0);
        unset($queryConfiguration['limit']);
        $queryConfiguration['fields'] .= ',' . $field . ' AS ' . self::ORDER_FIELD_ALIAS;
        $rows = (new QueryFacade())->getQueryResultByConfig($queryConfiguration);
        $rows = $this->sortRowsByValues($rows, $values);

        return $limit > 0 ? array_slice($rows, 0, $limit) : $rows;
    }

    /**
     * Sorts rows by the position of their order field in the given values.
     * Values are compared case-insensitively and without trailing spaces,
     * like most database collations do. Rows without a matching value
     * are moved to the end.
     *
     * @param string[] $values
     */
    protected function sortRowsByValues(array $rows, array $values): array
    {
        $positions = [];
        foreach (array_values($values) as $position => $value) {
            $positions[self::normalizeOrderValue($value)] ??= $position;
        }
        $last = count($positions);

        usort(
            $rows,
            static fn(array $a, array $b): int => ($positions[self::normalizeOrderValue($a[self::ORDER_FIELD_ALIAS])] ?? $last)
                <=> ($positions[self::normalizeOrderValue($b[self::ORDER_FIELD_ALIAS])] ?? $last)
        );

        return array_map(
            static function (array $row): array {
                unset($row[self::ORDER_FIELD_ALIAS]);
                return $row;
            },
            $rows
        );
    }

    protected static function normalizeOrderValue(mixed $value): string
    {
        return mb_strtolower(rtrim((string)$value, ' '));
    }

    /**
     * An empty IN constraint matches nothing unless it is combined with OR
     *
     * @param string[]|null $inValues
     */
    protected function isAlwaysEmpty(array $constraints, ?array $inValues): bool
    {
        return $inValues === []
            && !isset($constraints['OR']);
    }

    /**
     * Returns the (prefixed) values of the IN constraint or null
     * if there is no IN constraint
     *
     * @return string[]|null
     * @throws InvalidConfigurationException
     */
    protected function getInConstraintValues(array $record, array $queryConfiguration): ?array
    {
        $operatorConfig = $queryConfiguration['where']['IN'] ?? null;
        if (!is_array($operatorConfig) || !isset($operatorConfig['values'], $operatorConfig['field'])) {
            return null;
        }
        $childConfig = $operatorConfig['values'];
        if (!is_array($childConfig) || !isset($childConfig['field']) || !is_array($record[$childConfig['field']] ?? null)) {
            throw new InvalidConfigurationException('Error while parsing configuration for operator `IN`');
        }
        $prefix = (string)($childConfig['prefix'] ?? '');

        return array_map(
            static fn($value): string => $prefix . $value,
            array_values($record[$childConfig['field']])
        );
    }

    protected function getQueryConfiguration($configuration): array
    {
        $queryConfiguration = SelectQuery::DEFAULT_CONFIGURATION;

        ArrayUtility::mergeRecursiveWithOverrule(
            $queryConfiguration,
            $configuration[self::CONFIGURATION_KEY_QUERY],
            true,
            false
        );

        return $queryConfiguration;
    }

    /**
     * Parses the constraints of a query configuration into a
     * WHERE clause
     *
     * @param string[]|null $inValues values of the IN constraint, determined if not given
     * @return array Parsed query configuration
     * @throws InvalidConfigurationException
     */
    protected function parseQueryConstraints(array $record, array $queryConfiguration, ?array $inValues = null): array
    {
        if (empty($queryConfiguration['where'])
            || !is_array($queryConfiguration['where'])
        ) {
            return $queryConfiguration;
        }
        $connection = $this->connectionPool->getConnectionForTable($queryConfiguration['table']);
        $whereClause = '';
        foreach ($queryConfiguration['where'] as $operator => $operatorConfig) {
            if ($operator === 'AND' || $operator === 'OR') {
                if ($whereClause === '' && $operator === 'AND') {
                    $operator = '';
                }
                $whereClause .= $operator . ' ' . $operatorConfig['condition'];
                $prefix = '';
                if (isset($operatorConfig['prefix'])) {
                    $prefix .= $operatorConfig['prefix'];
                }

                if (isset($operatorConfig['value'])) {
                    //read field value from record
                    $whereClause .= $connection->quote($prefix . $record[$operatorConfig['value']]);
                }
            }

            if ($operator === 'IN') {
                $values = $inValues ?? $this->getInConstraintValues($record, $queryConfiguration);
                if ($values === null) {
                    continue;
                }
                if ($whereClause !== '') {
                    $whereClause .= ' AND';
                }
                if ($values === []) {
                    // IN () is invalid SQL, an empty set matches nothing
                    $whereClause .= ' 1 = 0 ';
                    continue;
                }
                $quotedValues = array_map(
                    static fn(string $value): string => $connection->quote($value),
                    $values
                );
                $whereClause .= sprintf(' %s IN (%s) ', $operatorConfig['field'], implode(',', $quotedValues));
            }
        }
        $queryConfiguration['where'] = $whereClause;

        return $queryConfiguration;
    }

    /**
     * Maps fields
     *
     * @param array $record target record
     * @param array $source Source: record
     * @param array $config Mapping configuration
     */
    protected function mapFields(&$record, $source, $config)
    {
        if (!isset($config['fields'])
            || !is_array($config['fields'])
        ) {
            return;
        }
        foreach ($config['fields'] as $fieldName => $singleConfig) {
            if (isset($singleConfig['mapTo'])
                && is_string($singleConfig['mapTo'])
            ) {
                $record[$singleConfig['mapTo']] = $source[$fieldName];
            }
        }
    }
}
