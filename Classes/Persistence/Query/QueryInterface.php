<?php

namespace CPSIT\T3importExport\Persistence\Query;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

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
interface QueryInterface
{
    public const TABLE = 'table';
    public const FIELDS = 'fields';
    public const WHERE = 'where';
    public const GROUP_BY = 'groupBy';
    public const ORDER_BY = 'orderBy';
    public const LIMIT = 'limit';

    /**
     * Constants representing the direction when ordering result sets.
     */
    const ORDER_ASCENDING = 'ASC';
    const ORDER_DESCENDING = 'DESC';

    /**
     * Type of query (select, count, delete, update...)
     */
    public const TYPE = 'type';

    /**
     * Build a query demand from configuration array
     *
     * @param array $config
     * @return $this
     */
    public function withConfiguration(array $config): self;

    public function build(): QueryBuilder;
}
