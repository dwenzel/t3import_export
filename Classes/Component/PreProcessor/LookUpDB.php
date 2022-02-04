<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Persistence\Query\SelectQuery;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
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

/**
 * Class LookUpDB
 * Base class for database look up.
 * Children must implement PreProcessorInterface
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class LookUpDB extends AbstractPreProcessor implements PreProcessorInterface
{
    use DatabaseTrait;

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
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
        if (isset($configuration['identifier'])
            && !is_string($configuration['identifier'])
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
     * @return bool
     */
    public function process($configuration, &$record)
    {
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
        try {
            $queryConfiguration = $this->parseQueryConstraints($record, $queryConfiguration);
        } catch (InvalidConfigurationException $e) {
            // todo: log error
            return false;
        }

        if (!empty($queryConfiguration['singleRow'])) {
            $queryConfiguration['limit'] = 1;
        }
        $queryResult = (new SelectQuery())->withConfiguration($queryConfiguration)
            ->build()
            ->execute()
            ->fetchAllAssociative();

        $targetField = $configuration['targetField'];

        if (empty($queryResult)
            && isset($record[$targetField])
        ) {
            unset($record[$targetField]);
            return true;
        }

        if ($queryConfiguration['singleRow']) {
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
     * @param $configuration
     * @return array
     */
    protected function getQueryConfiguration($configuration): array
    {
        $queryConfiguration = SelectQuery::DEFAULT_CONFIGURATION;

        ArrayUtility::mergeRecursiveWithOverrule(
            $queryConfiguration,
            $configuration['select'],
            true,
            false
        );

        return $queryConfiguration;
    }

    /**
     * Parses the constraints of a query configuration into a
     * WHERE clause
     *
     * @param $record
     * @param $queryConfiguration
     * @return array | FALSE Parsed query configuration
     */
    protected function parseQueryConstraints(array $record, array $queryConfiguration): array
    {
        if (empty($queryConfiguration['where'])
            || !is_array($queryConfiguration['where'])
        ) {
            return $queryConfiguration;
        }
        $queryBuilder = $this->connectionPool->getConnectionForTable($queryConfiguration['table']);
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
                    $whereClause .= $prefix .
                        $queryBuilder->quote($record[$operatorConfig['value']]);
                }
            }

            if ($operator === 'IN') {
                if (isset($operatorConfig['values'], $operatorConfig['field'])) {
                    $childConfig = $operatorConfig['values'];
                    $sourceField = $operatorConfig['field'];

                    if (!is_array($childConfig) || !isset($childConfig['field']) || !is_array($record[$childConfig['field']])
                    ) {
                        throw (new InvalidConfigurationException('Error while parsing configuration for operator `'));
                    }
                    $childField = $childConfig['field'];

                    $children = $record[$childField];
                    $prefix = $order = '';
                    $childValues = [];

                    if (isset($childConfig['prefix'])) {
                        $prefix = $childConfig['prefix'];
                    }

                    foreach ($children as $childValue) {
                        $childValues[] = sprintf('"%s%s"', $prefix, $childValue);
                    }
                    $childValueList = implode(',', $childValues);

                    if (isset($operatorConfig['keepOrder'])) {
                        $order = sprintf('ORDER BY FIELD (%s,%s)', $sourceField, $childValueList);
                    }

                    $whereClause .= sprintf(' %s IN (%s) %s', $sourceField, $childValueList, $order);
                }
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
