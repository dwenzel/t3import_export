<?php

namespace CPSIT\T3importExport\Persistence;

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

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

/**
 * Dummy data target - does not persist anything
 */
class DataTargetDummy implements DataTargetInterface
{
    /**
     * Dummy method to make component compatible wit DataTargetInterface
     *
     * @param array|DomainObjectInterface $object
     * @param array|null $configuration
     * @return bool|mixed
     */
    public function persist($object, array $configuration = null)
    {
        return true;
    }


    /**
     * Dummy method to make component compatible wit DataTargetInterface
     *
     * @param null $result
     * @param array|null $configuration
     * @return void
     */
    public function persistAll($result = null, array $configuration = null)
    {
    }
}
