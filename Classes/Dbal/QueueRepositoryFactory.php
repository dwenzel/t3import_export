<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Dbal;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\ImportExportCore\Dbal\Queue\DbalQueueRepository;

/**
 * Creates the queue repository of the core on the queue table of the
 * extension.
 *
 * The table is defined in `ext_tables.sql` and created by TYPO3; its
 * structure follows `DbalQueueRepository::getTables()`. The connection is the
 * one the table is mapped to in
 * `$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']`, `Default` without
 * mapping.
 */
final readonly class QueueRepositoryFactory
{
    public const string TABLE = 'tx_t3importexport_queue_item';

    public function __construct(private Typo3ConnectionProvider $connectionProvider) {}

    public function create(): DbalQueueRepository
    {
        return new DbalQueueRepository($this->connectionProvider->getConnectionForTable(self::TABLE), self::TABLE);
    }
}
