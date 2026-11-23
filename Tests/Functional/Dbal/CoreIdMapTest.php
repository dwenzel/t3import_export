<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Dbal;

use CPSIT\ImportExportCore\Dbal\State\DbalIdMap;
use CPSIT\ImportExportCore\Service\DataTransferProcessor;
use CPSIT\ImportExportCore\State\Component\ResolveReferencePreProcessor;
use CPSIT\ImportExportCore\State\Component\TargetIdPreProcessor;
use CPSIT\ImportExportCore\State\Component\UnchangedRecordFilter;
use CPSIT\ImportExportCore\State\IdMapInterface;
use CPSIT\ImportExportCore\State\IdMapping;
use CPSIT\T3importExport\Dbal\IdMapFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The ID map of the core on the ID map table of the extension, and the
 * services that get it.
 */
#[CoversClass(IdMapFactory::class)]
class CoreIdMapTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string SECOND_CONNECTION = 'Second';

    #[\Override]
    protected function tearDown(): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION])) {
            $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->close();
            unset(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION],
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][IdMapFactory::TABLE]
            );
        }
        parent::tearDown();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->select(['namespace', 'source_id', 'target_id', 'checksum'], IdMapFactory::TABLE, [], [], ['namespace' => 'ASC', 'source_id' => 'ASC'])
            ->fetchAllAssociative();
    }

    /**
     * Reads a protected property of an object of the core
     */
    private static function property(object $object, string $name): mixed
    {
        return (new \ReflectionProperty($object, $name))->getValue($object);
    }

    #[Test]
    public function idMapOfTheContainerIsBuiltByTheFactory(): void
    {
        $idMap = $this->get(IdMapInterface::class);

        self::assertInstanceOf(DbalIdMap::class, $idMap);
        self::assertSame($idMap, $this->get(DbalIdMap::class));
        self::assertSame(IdMapFactory::TABLE, $idMap->getTables()[0]->getObjectName()->toString());
    }

    #[Test]
    public function processorGetsTheIdMap(): void
    {
        self::assertSame(
            $this->get(IdMapInterface::class),
            self::property($this->get(DataTransferProcessor::class), 'idMap')
        );
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function stateComponentsDataProvider(): array
    {
        return [
            'TargetIdPreProcessor' => [TargetIdPreProcessor::class],
            'ResolveReferencePreProcessor' => [ResolveReferencePreProcessor::class],
            'UnchangedRecordFilter' => [UnchangedRecordFilter::class],
        ];
    }

    /**
     * Components are created by `Typo3Instantiator` with
     * `GeneralUtility::makeInstance()`.
     *
     * @param class-string $className
     */
    #[Test]
    #[DataProvider('stateComponentsDataProvider')]
    public function stateComponentsGetTheIdMap(string $className): void
    {
        self::assertSame(
            $this->get(IdMapInterface::class),
            self::property(GeneralUtility::makeInstance($className), 'idMap')
        );
    }

    #[Test]
    public function mappingsAreStoredInTheTable(): void
    {
        $idMap = $this->get(IdMapInterface::class);

        $idMap->set(new IdMapping('pages', '7', '42', 'checksum-1'));
        $idMap->set(new IdMapping('pages', '7', '43', 'checksum-2'));
        $idMap->set(new IdMapping('news', 'ä-1', '5'));

        self::assertSame(
            [
                ['namespace' => 'news', 'source_id' => 'ä-1', 'target_id' => '5', 'checksum' => ''],
                ['namespace' => 'pages', 'source_id' => '7', 'target_id' => '43', 'checksum' => 'checksum-2'],
            ],
            $this->fetchRows()
        );
        self::assertEquals(new IdMapping('pages', '7', '43', 'checksum-2'), $idMap->get('pages', '7'));
        self::assertFalse($idMap->has('pages', '8'));
        self::assertFalse($idMap->get('news', '7')->exists());
        self::assertEquals([new IdMapping('news', 'ä-1', '5')], iterator_to_array($idMap->all('news'), false));
    }

    #[Test]
    public function sourceIdsThatDifferInCaseHaveTheirOwnMapping(): void
    {
        $idMap = $this->get(IdMapInterface::class);

        $idMap->set(new IdMapping('pages', 'ABC', '1'));
        $idMap->set(new IdMapping('pages', 'abc', '2'));
        $idMap->set(new IdMapping('Pages', 'abc', '3'));

        self::assertSame('1', $idMap->get('pages', 'ABC')->targetId);
        self::assertSame('2', $idMap->get('pages', 'abc')->targetId);
        self::assertSame('3', $idMap->get('Pages', 'abc')->targetId);
        self::assertCount(3, $this->fetchRows());
    }

    /**
     * `ext_tables.sql` sets `utf8mb4_bin` for `namespace` and `source_id`.
     * It is a PAD SPACE collation: trailing spaces are ignored.
     */
    #[Test]
    public function keyColumnsHaveABinaryCollationOnMysqlAndMariaDb(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(IdMapFactory::TABLE);
        if (!$connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            self::markTestSkipped('The collation is set on MySQL and MariaDB only.');
        }

        $collations = $connection->executeQuery(
            'SELECT COLUMN_NAME, COLLATION_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN (?, ?)'
            . ' ORDER BY COLUMN_NAME',
            [IdMapFactory::TABLE, 'namespace', 'source_id']
        )->fetchAllKeyValue();

        self::assertSame(['namespace' => 'utf8mb4_bin', 'source_id' => 'utf8mb4_bin'], $collations);
    }

    #[Test]
    public function tableMappingSelectsTheConnection(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][IdMapFactory::TABLE] = self::SECOND_CONNECTION;
        $second = $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION);
        $idMap = $this->get(IdMapFactory::class)->create();

        // `Second` points to the same database as `Default`; that a rollback
        // of `Second` removes the mapping proves that the ID map uses it
        $second->beginTransaction();
        $idMap->set(new IdMapping('pages', '1', '2'));
        self::assertTrue($idMap->has('pages', '1'));
        $second->rollBack();

        self::assertSame([], $this->fetchRows());
    }
}
