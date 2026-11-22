<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Dbal;

use CPSIT\ImportExportCore\Dbal\State\DbalIdMap;
use CPSIT\T3importExport\Dbal\IdMapFactory;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[CoversClass(IdMapFactory::class)]
class IdMapFactoryTest extends TestCase
{
    #[Test]
    public function tableIsTheIdMapTableOfTheExtension(): void
    {
        self::assertSame('tx_t3importexport_id_map', IdMapFactory::TABLE);
    }

    #[Test]
    public function createReturnsIdMapOnTheConnectionOfTheIdMapTable(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with(IdMapFactory::TABLE)
            ->willReturn($this->createStub(Connection::class));
        $subject = new IdMapFactory(new Typo3ConnectionProvider($connectionPool));

        $idMap = $subject->create();

        self::assertInstanceOf(DbalIdMap::class, $idMap);
    }

    #[Test]
    public function createdIdMapUsesTheIdMapTable(): void
    {
        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($this->createStub(Connection::class));
        $subject = new IdMapFactory(new Typo3ConnectionProvider($connectionPool));

        $tables = $subject->create()->getTables();

        self::assertCount(1, $tables);
        self::assertSame(IdMapFactory::TABLE, $tables[0]->getObjectName()->toString());
    }
}
