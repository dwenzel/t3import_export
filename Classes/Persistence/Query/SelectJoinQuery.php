<?php

declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
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
 * Build up an SQL query by configuration for SELECT-JOIN like
 *
 * 80 {
    class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
    config {

        targetField = institution
        select {
            table = tx_foo
            fields = item.uid

            leftJoin {
                fromAlias = opposite
                table = tx_bar
                alias = idem
                condition = idem.uid = opposite.baz_blo
            }

            where {
                AND {
                    value = external_foo
                    parameterType = string
                    condition = deleted=0 AND idem.blu_faz='value' AND opposite.some_filter_field=
                }
            }

            singleRow = 1
        }

        # optional considered in mapper, not here...
        fields {
            uid.mapTo = __identity
        }
    }
* }
 *
 */
class SelectJoinQuery extends AbstractTemplateQuery
{
    protected function buildSelect(): QueryInterface
    {
        $this->queryBuilder->select(...GeneralUtility::trimExplode(',', $this->config[QueryInterface::FIELDS], true))
            ->from(
                $this->config[QueryInterface::TABLE],
                $this->config[QueryInterface::LEFT_JOIN][QueryInterface::FROM_ALIAS] ?? null);

        $this->setJoin();

        return $this;
    }

    final protected function setJoin(): void
    {
        if (!empty($this->config[QueryInterface::LEFT_JOIN])) {
            $this->queryBuilder->leftJoin(
                $this->config[QueryInterface::LEFT_JOIN][QueryInterface::FROM_ALIAS],
                $this->config[QueryInterface::LEFT_JOIN][QueryInterface::TABLE],
                $this->config[QueryInterface::LEFT_JOIN][QueryInterface::ALIAS],
                $this->config[QueryInterface::LEFT_JOIN][QueryInterface::JOIN_CONDITION]
            );
        }
    }

}
