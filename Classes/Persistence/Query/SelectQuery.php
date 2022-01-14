<?php

namespace CPSIT\T3importExport\Persistence\Query;

use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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
class SelectQuery implements QueryInterface
{
    use DatabaseTrait;

    public const MESSAGE_MISSING_FIELD = 'Field `%s` must not be empty';
    public const CODE_MISSING_FIELD = 1642072670;

    public const DEFAULT_CONFIGURATION = [
        QueryInterface::FIELDS => '*',
        QueryInterface::WHERE => '',
        QueryInterface::GROUP_BY => '',
        QueryInterface::ORDER_BY => '',
        QueryInterface::LIMIT => ''
    ];

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $builder;

    /**
     * @param array $config
     * @return QueryInterface
     * @throws InvalidConfigurationException
     */
    public function withConfiguration(array $config): QueryInterface
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


        $this->builder = $this->connectionPool->getConnectionForTable(
            $config[QueryInterface::TABLE]
        )->createQueryBuilder();

        $this->builder->select($this->config[QueryInterface::FIELDS])
            ->from($this->config[QueryInterface::TABLE]);

        if (!empty($config[QueryInterface::WHERE])) {
            $this->builder->where($this->config[QueryInterface::WHERE]);
        }

        if (!empty($config[QueryInterface::GROUP_BY])) {
            $this->builder->groupBy($this->config[QueryInterface::GROUP_BY]);
        }

        if (!empty($config[QueryInterface::ORDER_BY])) {
            $this->builder->orderBy($this->config[QueryInterface::ORDER_BY]);;
        }

        if (!empty($config[QueryInterface::LIMIT])) {
            $this->builder->setMaxResults((int)$this->config[QueryInterface::LIMIT]);
        }

        return $this;
    }

    public function build(): QueryBuilder
    {
        return $this->builder;
    }
}
