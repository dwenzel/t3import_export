<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Dbal;

use CPSIT\ImportExportCore\Dbal\State\DbalIdMap;
use CPSIT\T3importExport\Dbal\IdMapFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Detects differences between the ID map table in `ext_tables.sql` and the
 * table the core defines for it (`DbalIdMap::getTables()`).
 */
#[CoversClass(IdMapFactory::class)]
class IdMapTableSchemaTest extends CoreTableSchemaTestCase
{
    #[\Override]
    protected function getTableName(): string
    {
        return IdMapFactory::TABLE;
    }

    #[\Override]
    protected function createTableProvider(Connection $connection, string $table): DbalIdMap
    {
        return new DbalIdMap($connection, $table);
    }

    #[\Override]
    protected function getExpectedIndexCount(): int
    {
        return 0;
    }
}
