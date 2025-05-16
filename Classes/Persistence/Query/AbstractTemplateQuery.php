<?php

declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  (c) 2022 Arend Maubach <a.maubach@cps-it.de>
 *
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace CPSIT\T3importExport\Persistence\Query;

use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Divides into SELECT and SELECT JOIN database request.
 *
 * Consideration of structure:
 * Dividing similarities from differences,
 * THUS using strict Template Method Pattern (abstract, extends ands its strong binding).
 * @link https://en.wikipedia.org/wiki/Template_method_pattern
 *
 * Future: To get up to INSERT, UPDATE... UPDATE-SELECT consider to use DECORATOR or s.th. else. But no early complexity here.
 * But please consider getting rit of fluent interface, to tight binding, dependency on call order.
 *
 * QueryFacadeInterface->withConfiguration($queryConfiguration)
        ->setQuery()
        ->build()
        //switch to TYPO3\CMS\Core\Database\Query\QueryBuilder
        ->execute()
        ->fetchAllAssociative();
 *
 * @see QueryFacade
 */
abstract class AbstractTemplateQuery implements QueryInterface
{
    use DatabaseTrait;

    public const MESSAGE_MISSING_FIELD = 'Field `%s` must not be empty';
    public const CODE_MISSING_FIELD = 1_642_072_670;
    public const DEFAULT_DATABASE_IDENTIFIER = 'Default';

    public const DEFAULT_CONFIGURATION = [
        QueryInterface::TYPE => QueryInterface::DEFAULT_TYPE,
        QueryInterface::FIELDS => '*',
        QueryInterface::WHERE => '',
        QueryInterface::GROUP_BY => '',
        QueryInterface::ORDER_BY => '',
        QueryInterface::LIMIT => '',
    ];

    protected array $config = [];

    protected $databaseIdentifier = self::DEFAULT_DATABASE_IDENTIFIER;

    protected QueryBuilder $queryBuilder;

    public function withDatabaseIdentifier(string $identifier): self
    {
        $this->databaseIdentifier = $identifier;
        return clone $this;
    }

    /**
     * @throws InvalidConfigurationException
     */
    final public function withConfiguration(array $config): QueryInterface
    {
        if (empty($config[QueryInterface::TABLE])) {
            $message = sprintf(static::MESSAGE_MISSING_FIELD, QueryInterface::TABLE);

            throw new InvalidConfigurationException(
                $message,
                self::CODE_MISSING_FIELD
            );
        }

        $this->config = self::DEFAULT_CONFIGURATION;

        ArrayUtility::mergeRecursiveWithOverrule(
            $this->config,
            $config,
            true,
            false
        );

        $this->setQueryBuilder();

        return $this;
    }

    final protected function setQueryBuilder(): void
    {
        if (self::DEFAULT_DATABASE_IDENTIFIER !== $this->databaseIdentifier) {
            $connection = $this->connectionService->getDatabase($this->databaseIdentifier);
            $this->queryBuilder = $connection->createQueryBuilder();
        } else {
            $this->queryBuilder = $this->connectionPool->getConnectionForTable(
                $this->config[QueryInterface::TABLE]
            )->createQueryBuilder();
        }

        // Remove all TYPO3 defalt restrictions to allow full controll over typoscript
        $this->queryBuilder->getRestrictions()->removeAll();
    }

    final public function setQuery(): QueryInterface
    {
        $this->buildSelect();
        $this->buildJoin();
        $this->buildWhere();
        $this->buildGroupBy();
        $this->buildOrderBy();
        $this->buildLimit();

        return $this;
    }

    abstract protected function buildSelect(): QueryInterface;

    final public function build(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    final protected function buildWhere(): void
    {
        if (!empty($this->config[QueryInterface::WHERE])) {
            $this->queryBuilder->where($this->config[QueryInterface::WHERE]);
        }
    }

    protected function buildJoin(): void
    {
        if (
            empty($this->config[QueryInterface::JOIN])
            || !is_array($this->config[QueryInterface::JOIN])
            || empty($this->config[QueryInterface::JOIN][QueryInterface::TABLE]
            )
        ) {
            return;
        }

        $fromAlias = $this->config[QueryInterface::ALIAS] ?? $this->config[QueryInterface::TABLE];
        $joinAlias = $this->config[QueryInterface::JOIN][QueryInterface::ALIAS] ?? $this->config[QueryInterface::JOIN][QueryInterface::TABLE];
        $joinTable = $this->config[QueryInterface::JOIN][QueryInterface::TABLE] ?? $this->config[QueryInterface::TABLE];
        $joinCondition = $this->config[QueryInterface::JOIN][QueryInterface::JOIN_CONDITION] ?? $this->config[QueryInterface::JOIN_CONDITION][QueryInterface::TABLE];
        $this->queryBuilder->join(
            $fromAlias,
            $joinTable,
            $joinAlias,
            $joinCondition
        );
    }
    final protected function buildGroupBy(): void
    {
        if (!empty($this->config[QueryInterface::GROUP_BY])) {
            $this->queryBuilder->groupBy($this->config[QueryInterface::GROUP_BY]);
        }
    }

    final protected function buildOrderBy(): void
    {
        if (!empty($this->config[QueryInterface::ORDER_BY])) {
            $this->composeOrderBy($this->queryBuilder, $this->config[QueryInterface::ORDER_BY]);
        }
    }

    final protected function composeOrderBy(QueryBuilder $queryBuilder, string $orderBy): void
    {
        $sorting = GeneralUtility::trimExplode(',', $orderBy, true);

        if (!empty($sorting)) {
            foreach ($sorting as $orderItem) {
                [$orderField, $ascDesc] = GeneralUtility::trimExplode(' ', $orderItem, true);
                // count == 1 means that no direction is given
                if ($ascDesc) {
                    $ascDesc = ((strtolower((string)$ascDesc) === 'desc') ?
                        QueryInterface::ORDER_DESCENDING :
                        QueryInterface::ORDER_ASCENDING);
                } else {
                    $ascDesc = QueryInterface::ORDER_ASCENDING;
                }

                $queryBuilder->addOrderBy($orderField, $ascDesc);
            }
        }
    }

    final protected function buildLimit(): void
    {
        if (!empty($this->config[QueryInterface::LIMIT])) {
            $this->queryBuilder->setMaxResults((int)$this->config[QueryInterface::LIMIT]);
        }
    }
}
