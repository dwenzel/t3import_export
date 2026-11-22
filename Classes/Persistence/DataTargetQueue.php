<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\Persistence\DataTargetQueue as CoreDataTargetQueue;
use CPSIT\T3importExport\Utility\DeprecationUtility;

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
 * Writes the field `data` of records into the queue of the core (table
 * `tx_t3importexport_queue_item`).
 *
 * Adapter on the queue target of the core. It enqueues the record
 * `['data' => '<data as string>']`, as the queue of earlier versions stored
 * only this field as payload. The upgrade wizard migrates the items of
 * earlier versions to the same record, so that records queued before are
 * recognised as duplicates. Other fields of the record are ignored.
 *
 * Configuration:
 *
 * - `identifier` (string, required): the queue
 * - `allowUpdate` (bool, default false): records already in the queue are set
 *   back to new. Other values than booleans are deprecated and cast to bool.
 *
 * Records without a scalar field `data` and objects are not written
 * (`persist()` returns false).
 */
class DataTargetQueue extends CoreDataTargetQueue
{
    final public const string FIELD_DATA = 'data';

    /**
     * @deprecated since 4.0, will be removed in 5.0: the queue is configured
     * with `identifier` of the target, not in the record.
     */
    final public const string FIELD_IDENTIFIER = 'identifier';

    public function isConfigurationValid(array $configuration): bool
    {
        return parent::isConfigurationValid($this->normalizeConfiguration($configuration, false));
    }

    public function persist(array|object $object, ?array $configuration = null): bool
    {
        if (!is_array($object)) {
            return false;
        }
        $data = $object[self::FIELD_DATA] ?? null;
        if (!is_scalar($data)) {
            return false;
        }
        $configuration = $this->normalizeConfiguration($configuration ?? $this->configuration, true);
        $queue = $object[self::FIELD_IDENTIFIER] ?? null;
        if (is_string($queue) && $queue !== '') {
            DeprecationUtility::trigger(
                'The queue of DataTargetQueue is taken from the field "identifier" of the record. This is deprecated'
                . ' since 4.0 and will be removed in 5.0. Configure the queue with "identifier" of the target.',
                DeprecationUtility::DATABASE_ISSUE_URL
            );
            $configuration[self::KEY_IDENTIFIER] = $queue;
        }

        return parent::persist([self::FIELD_DATA => (string)$data], $configuration);
    }

    /**
     * Casts integers and strings in `allowUpdate` to bool, like earlier
     * versions did.
     *
     * @param array<array-key, mixed> $configuration
     * @return array<array-key, mixed>
     */
    protected function normalizeConfiguration(array $configuration, bool $notify): array
    {
        $allowUpdate = $configuration[self::KEY_ALLOW_UPDATE] ?? null;
        if (!is_string($allowUpdate) && !is_int($allowUpdate)) {
            return $configuration;
        }
        if ($notify) {
            DeprecationUtility::trigger(
                'Other values than true and false in the option "allowUpdate" of DataTargetQueue are deprecated since 4.0'
                . ' and will not be accepted in 5.0.',
                DeprecationUtility::DATABASE_ISSUE_URL
            );
        }
        $configuration[self::KEY_ALLOW_UPDATE] = (bool)$allowUpdate;

        return $configuration;
    }
}
