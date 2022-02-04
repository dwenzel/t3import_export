<?php

namespace CPSIT\T3importExport\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class FactoryFactory
{
    protected FactoryMapInterface $factoryMap;

    public function __construct(FactoryMapInterface $factoryMap = null)
    {
        $this->factoryMap = $factoryMap ?? GeneralUtility::makeInstance(ComponentFactoryMap::class);
    }

    /**
     * Returns a factory able to provide an instance of a product
     *
     * @param string $productClass Product
     * @return FactoryInterface
     */
    public function get(string $productClass): FactoryInterface
    {
        $factoryClass = $this->factoryMap->resolve($productClass);

        return GeneralUtility::makeInstance($factoryClass);
    }
}
