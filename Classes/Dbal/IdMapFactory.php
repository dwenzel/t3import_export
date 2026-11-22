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

use CPSIT\ImportExportCore\Dbal\State\DbalIdMap;

/**
 * Creates the ID map of the core on the ID map table of the extension.
 *
 * The processor of the core records the target ID of each written record in
 * this map (task option `idMap`); `TargetIdPreProcessor`,
 * `ResolveReferencePreProcessor` and `UnchangedRecordFilter` read it.
 *
 * The table is defined in `ext_tables.sql` and created by TYPO3; its
 * structure follows `DbalIdMap::getTables()`. The connection is the one the
 * table is mapped to in `$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']`,
 * `Default` without mapping.
 */
final readonly class IdMapFactory
{
    public const string TABLE = 'tx_t3importexport_id_map';

    public function __construct(private Typo3ConnectionProvider $connectionProvider) {}

    public function create(): DbalIdMap
    {
        return new DbalIdMap($this->connectionProvider->getConnectionForTable(self::TABLE), self::TABLE);
    }
}
