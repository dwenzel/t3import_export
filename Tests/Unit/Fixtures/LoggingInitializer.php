<?php

namespace CPSIT\T3importExport\Tests\Unit\Fixtures;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
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

use CPSIT\T3importExport\Component\Initializer\AbstractInitializer;
use CPSIT\T3importExport\LoggingInterface;

/**
 * Class LoggingInitializer
 * Dummy class for testing: Initializer implementing LoggingInterface
 */
class LoggingInitializer extends AbstractInitializer implements LoggingInterface
{
    /**
     * Gets all messages
     * @return array
     */
    public function getMessages()
    {
        return [];
    }

    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @return bool
     */
    public function process($configuration, &$records)
    {
        return true;
    }


}
