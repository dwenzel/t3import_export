<?php

declare(strict_types=1);

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

use CPSIT\T3importExport\Component\PostProcessor\AbstractPostProcessor;
use CPSIT\ImportExportCore\Component\PostProcessor\PostProcessorInterface;
use CPSIT\ImportExportCore\LoggingInterface;

/**
 * Class LoggingPostProcessor
 * Fake class for testing: PostProcessor implementing LoggingInterface
 * @deprecated
 */
class LoggingPostProcessor extends AbstractPostProcessor implements PostProcessorInterface, LoggingInterface
{
    /**
     * Gets all messages
     * @return array
     */
    public function getMessages(): array
    {
        return [];
    }

    public function getAndPurgeMessages(): array
    {
        return [];
    }


     public function process(array $configuration, mixed &$convertedRecord, array &$record): bool
    {
        return true;
    }
}
