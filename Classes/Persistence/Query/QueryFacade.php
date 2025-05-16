<?php

declare(strict_types=1);
/**
 * This file is part of the johanniter Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * README.md file that was distributed with this source code.
 */

namespace CPSIT\T3importExport\Persistence\Query;

use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Facade covers QueryFacadeInterface implementors chain
 * to receive query result. Direct calls still function.
 *
    QueryFacadeInterface->withConfiguration($queryConfiguration)
        ->setQuery()
        ->build()
        //switch to TYPO3\CMS\Core\Database\Query\QueryBuilder
        ->execute()
        ->fetchAllAssociative();
 *
 * @see AbstractTemplateQuery
 */
class QueryFacade implements QueryFacadeInterface
{
    private const array MAP_TYPE_CLASS = [
        QueryInterface::TYPE_SELECT => SelectQuery::class,
        QueryInterface::TYPE_SELECT_JOIN => SelectJoinQuery::class,
    ];

    public function getQueryResultByConfig(array $queryConfiguration): array
    {
        return ($this->getConcreteQueryInstance(
            $queryConfiguration[QueryInterface::TYPE]
        ))->withConfiguration($queryConfiguration)
            ->setQuery()
            ->build()
            ->execute()
            ->fetchAllAssociative();
    }

    /**
     * selects Query-Class to decide by configuration of "type"
     * 80 {
            class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
            config {
                targetField = foo
                select {
                    --> type = selectJoin <--
                    table = tx_foo
                    ...
                    }
                }
            }
     * @see SelectQuery, SelectJoinQuery ...
     */
    protected function getConcreteQueryInstance(string $type): QueryInterface
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnectionService = GeneralUtility::makeInstance(DatabaseConnectionService::class);
        $class = array_key_exists($type, self::MAP_TYPE_CLASS)
            ? self::MAP_TYPE_CLASS[$type]
            : self::MAP_TYPE_CLASS[QueryInterface::TYPE_SELECT];

        /**
         * Note: we must NOT use GeneralUtility::makeInstance here in order to receive a clean instance
         * every time.
         */
        $instance = new $class($connectionPool, $databaseConnectionService);

        return $instance;
    }
}
