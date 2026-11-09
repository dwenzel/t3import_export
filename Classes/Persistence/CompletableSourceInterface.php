<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
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
 * A data source that has to be told when the records it returned
 * have been processed, e.g. to mark queue items as finished.
 *
 * The DataTransferProcessor calls complete() after the task has been
 * persisted and its finishers have run, and fail() when the task throws.
 * Both receive the configuration getRecords() was called with.
 */
interface CompletableSourceInterface
{
    public function complete(array $configuration): void;

    public function fail(array $configuration): void;
}
