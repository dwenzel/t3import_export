<?php

declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  (c) 2022 Arend Maubach <a.maubach@cps-it.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Build up an SQL query by configuration for SELECT like
 *
 * 42 {
    class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
    config {
        targetField = foo
            select {
                type = select
                table = tx_foo
                fields = bar
                where {
                    AND {
                        value = bar_external
                        parameterType = string
                        condition = deleted=0 AND foo_bar=
                    }
                }

                singleRow = 1
            }
        }

        # optional considered in mapper, not here...
        fields {
            uid.mapTo = __identity
        }
    }
 * }
 *
 */
class SelectQuery extends AbstractTemplateQuery
{
    protected function buildSelect(): QueryInterface
    {
        $this->queryBuilder->select(...GeneralUtility::trimExplode(',', $this->config[QueryInterface::FIELDS], true))
            ->from($this->config[QueryInterface::TABLE]);

        return $this;
    }
}
