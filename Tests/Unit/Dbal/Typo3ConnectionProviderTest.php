<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Dbal;

use CPSIT\ImportExportCore\Dbal\ConnectionProviderInterface;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[CoversClass(Typo3ConnectionProvider::class)]
class Typo3ConnectionProviderTest extends TestCase
{
    private Typo3ConnectionProvider $subject;

    #[\Override]
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB'] = [
            'Connections' => [
                'Default' => ['driver' => 'pdo_sqlite'],
                'Legacy' => ['driver' => 'pdo_sqlite'],
            ],
            'TableMapping' => [
                'tx_legacy_record' => 'Legacy',
                'tx_default_record' => 'Default',
            ],
        ];
        $this->subject = new Typo3ConnectionProvider($this->createStub(ConnectionPool::class));
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['DB']);
    }

    #[Test]
    public function implementsConnectionProviderInterface(): void
    {
        self::assertInstanceOf(ConnectionProviderInterface::class, $this->subject);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function connectionNamesDataProvider(): array
    {
        return [
            'default name of the core' => ['default', true],
            'default name of TYPO3' => ['Default', true],
            'other configured connection' => ['Legacy', true],
            'not configured' => ['Missing', false],
            'other case of a configured connection' => ['legacy', false],
            'empty name' => ['', false],
        ];
    }

    #[Test]
    #[DataProvider('connectionNamesDataProvider')]
    public function hasConnectionReturnsWhetherTheConnectionIsConfigured(string $name, bool $expected): void
    {
        self::assertSame($expected, $this->subject->hasConnection($name));
    }

    #[Test]
    public function hasConnectionReturnsFalseWithoutConfiguredConnections(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']);

        self::assertFalse($this->subject->hasConnection('default'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function connectionNameMappingDataProvider(): array
    {
        return [
            'default name of the core' => ['default', 'Default'],
            'default name of TYPO3' => ['Default', 'Default'],
            'other connection' => ['Legacy', 'Legacy'],
        ];
    }

    #[Test]
    #[DataProvider('connectionNameMappingDataProvider')]
    public function getConnectionReturnsTheConnectionOfTheConnectionPool(string $name, string $typo3Name): void
    {
        $connection = $this->createStub(Connection::class);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->once())
            ->method('getConnectionByName')
            ->with($typo3Name)
            ->willReturn($connection);

        $subject = new Typo3ConnectionProvider($connectionPool);

        self::assertSame($connection, $subject->getConnection($name));
    }

    #[Test]
    public function getConnectionThrowsForAConnectionThatIsNotConfigured(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->never())->method('getConnectionByName');
        $subject = new Typo3ConnectionProvider($connectionPool);

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(1795351767);

        $subject->getConnection('Missing');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function tableMappingDataProvider(): array
    {
        return [
            'table without mapping' => ['pages', 'default'],
            'table mapped to the default connection' => ['tx_default_record', 'default'],
            'table mapped to another connection' => ['tx_legacy_record', 'Legacy'],
        ];
    }

    #[Test]
    #[DataProvider('tableMappingDataProvider')]
    public function getConnectionNameForTableReturnsTheNameForTheCore(string $table, string $expected): void
    {
        self::assertSame($expected, $this->subject->getConnectionNameForTable($table));
    }

    #[Test]
    public function getConnectionNameForTableReturnsDefaultWithoutTableMapping(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']);

        self::assertSame('default', $this->subject->getConnectionNameForTable('tx_legacy_record'));
    }

    #[Test]
    public function getConnectionNameForTableIgnoresAnEmptyMapping(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] = '';

        self::assertSame('default', $this->subject->getConnectionNameForTable('pages'));
    }

    #[Test]
    public function getConnectionForTableReturnsTheConnectionOfTheTableMapping(): void
    {
        $connection = $this->createStub(Connection::class);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with('tx_legacy_record')
            ->willReturn($connection);
        $subject = new Typo3ConnectionProvider($connectionPool);

        self::assertSame($connection, $subject->getConnectionForTable('tx_legacy_record'));
    }

    #[Test]
    public function getConnectionForTableReturnsTheSameConnectionAsTheMappedName(): void
    {
        $connection = $this->createStub(Connection::class);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->once())->method('getConnectionForTable')->willReturn($connection);
        $connectionPool->expects($this->once())->method('getConnectionByName')->with('Legacy')->willReturn($connection);
        $subject = new Typo3ConnectionProvider($connectionPool);

        self::assertSame(
            $subject->getConnection($subject->getConnectionNameForTable('tx_legacy_record')),
            $subject->getConnectionForTable('tx_legacy_record')
        );
    }
}
