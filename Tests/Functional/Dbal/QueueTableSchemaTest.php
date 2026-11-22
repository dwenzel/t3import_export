<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Dbal;

use CPSIT\ImportExportCore\Dbal\Queue\DbalQueueRepository;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Detects differences between the queue table in `ext_tables.sql` and the
 * table the core defines for it (`DbalQueueRepository::getTables()`).
 */
#[CoversClass(QueueRepositoryFactory::class)]
class QueueTableSchemaTest extends CoreTableSchemaTestCase
{
    #[\Override]
    protected function getTableName(): string
    {
        return QueueRepositoryFactory::TABLE;
    }

    #[\Override]
    protected function createTableProvider(Connection $connection, string $table): DbalQueueRepository
    {
        return new DbalQueueRepository($connection, $table);
    }

    #[\Override]
    protected function getExpectedIndexCount(): int
    {
        return 2;
    }
}
