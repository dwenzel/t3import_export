<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Dbal;

use CPSIT\ImportExportCore\Dbal\Schema\SchemaInstaller;
use CPSIT\ImportExportCore\Dbal\Schema\TableProviderInterface;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Detects differences between a table of the core in `ext_tables.sql` and the
 * table the core defines for it (`TableProviderInterface::getTables()`).
 *
 * The columns are compared with a reference table that the core creates in
 * the same database, so both are read back by the same platform. Indexes are
 * compared with the definition of the core directly.
 *
 * SQLite does not distinguish `int` from `bigint` or `text` from `longtext`,
 * so the full check of the column types needs MySQL (as in CI).
 */
abstract class CoreTableSchemaTestCase extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string REFERENCE_TABLE = 'core_reference_table';

    /**
     * Name of the table in `ext_tables.sql`
     */
    abstract protected function getTableName(): string;

    /**
     * The component of the core that defines the table, on the given table
     */
    abstract protected function createTableProvider(Connection $connection, string $table): TableProviderInterface;

    /**
     * Number of indexes and unique constraints besides the primary key
     */
    abstract protected function getExpectedIndexCount(): int;

    #[\Override]
    protected function tearDown(): void
    {
        $schemaManager = $this->getConnection()->createSchemaManager();
        if ($schemaManager->tablesExist([self::REFERENCE_TABLE])) {
            $schemaManager->dropTable(self::REFERENCE_TABLE);
        }
        parent::tearDown();
    }

    private function getConnection(): Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable($this->getTableName());
    }

    private function getCoreTable(): Table
    {
        $tables = $this->createTableProvider($this->getConnection(), $this->getTableName())->getTables();
        self::assertCount(1, $tables);

        return $tables[0];
    }

    private function introspect(string $table): Table
    {
        return $this->getConnection()->createSchemaManager()->introspectTable($table);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function describeColumns(Table $table): array
    {
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getObjectName()->getIdentifier()->getValue()] = [
                'type' => Type::getTypeRegistry()->lookupName($column->getType()),
                'length' => $column->getLength(),
                'precision' => $column->getPrecision(),
                'scale' => $column->getScale(),
                'unsigned' => $column->getUnsigned(),
                'fixed' => $column->getFixed(),
                'notNull' => $column->getNotnull(),
                'default' => $column->getDefault(),
                'autoincrement' => $column->getAutoincrement(),
            ];
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(Table $table): array
    {
        return array_map(
            static fn ($name): string => $name->getIdentifier()->getValue(),
            $table->getPrimaryKeyConstraint()?->getColumnNames() ?? []
        );
    }

    /**
     * Unique constraints and indexes except the primary key, by name.
     *
     * @param (callable(string): string)|null $mapName
     * @return array<string, array{unique: bool, columns: list<string>}>
     */
    private function describeIndexes(Table $table, ?callable $mapName = null): array
    {
        $mapName ??= static fn (string $name): string => $name;
        $primaryKeyColumns = $this->primaryKeyColumns($table);
        $indexes = [];
        foreach ($table->getUniqueConstraints() as $constraint) {
            $name = (string)$constraint->getObjectName()?->getIdentifier()->getValue();
            $indexes[$mapName($name)] = [
                'unique' => true,
                'columns' => array_map(
                    static fn ($column): string => $column->getIdentifier()->getValue(),
                    $constraint->getColumnNames()
                ),
            ];
        }
        foreach ($table->getIndexes() as $index) {
            $columns = array_map(
                static fn ($column): string => $column->getColumnName()->getIdentifier()->getValue(),
                $index->getIndexedColumns()
            );
            $unique = $index->getType() === IndexType::UNIQUE;
            // the primary key is compared separately
            if ($unique && $columns === $primaryKeyColumns) {
                continue;
            }
            $indexes[$mapName($index->getObjectName()->getIdentifier()->getValue())] = [
                'unique' => $unique,
                'columns' => $columns,
            ];
        }
        ksort($indexes);

        return $indexes;
    }

    /**
     * TYPO3 adds a hash of table and index name to index names on platforms
     * where index names are unique per database.
     */
    private function typo3IndexName(string $name): string
    {
        $platform = $this->getConnection()->getDatabasePlatform();
        if ($platform instanceof SQLitePlatform || $platform instanceof PostgreSQLPlatform) {
            return $name . '_' . hash('crc32b', $this->getTableName() . '_' . $name);
        }

        return $name;
    }

    #[Test]
    public function tableHasNoTca(): void
    {
        self::assertArrayNotHasKey($this->getTableName(), $GLOBALS['TCA']);
    }

    #[Test]
    public function tableHasTheColumnsOfTheCoreSchema(): void
    {
        $connection = $this->getConnection();
        (new SchemaInstaller($connection))->install($this->createTableProvider($connection, self::REFERENCE_TABLE));

        $expected = $this->describeColumns($this->introspect(self::REFERENCE_TABLE));

        self::assertSame(
            array_keys($this->describeColumns($this->getCoreTable())),
            array_keys($expected),
            'The reference table has the columns of the core.'
        );
        self::assertSame($expected, $this->describeColumns($this->introspect($this->getTableName())));
    }

    #[Test]
    public function tableHasThePrimaryKeyOfTheCoreSchema(): void
    {
        self::assertNotSame([], $this->primaryKeyColumns($this->getCoreTable()));
        self::assertSame(
            $this->primaryKeyColumns($this->getCoreTable()),
            $this->primaryKeyColumns($this->introspect($this->getTableName()))
        );
    }

    #[Test]
    public function tableHasTheIndexesOfTheCoreSchema(): void
    {
        $expected = $this->describeIndexes($this->getCoreTable(), $this->typo3IndexName(...));

        self::assertCount($this->getExpectedIndexCount(), $expected);
        self::assertSame($expected, $this->describeIndexes($this->introspect($this->getTableName())));
    }

    #[Test]
    public function databaseCompareOfTypo3FindsNothingToUpdate(): void
    {
        $sqlReader = $this->get(SqlReader::class);
        $statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $table = $this->getTableName();
        self::assertNotSame(
            [],
            array_filter($statements, static fn (string $statement): bool => preg_match('/^CREATE TABLE `?' . $table . '`?\\s/', $statement) === 1),
            'ext_tables.sql defines the table.'
        );

        $suggestions = [];
        array_walk_recursive(
            $this->get(SchemaMigrator::class)->getUpdateSuggestions($statements),
            static function (mixed $statement) use (&$suggestions, $table): void {
                if (\is_string($statement) && str_contains($statement, $table)) {
                    $suggestions[] = $statement;
                }
            }
        );

        self::assertSame([], $suggestions);
    }

    #[Test]
    public function schemaInstallerOfTheCoreFindsNothingToAdd(): void
    {
        self::assertSame(
            [],
            (new SchemaInstaller($this->getConnection()))->getStatements(
                $this->createTableProvider($this->getConnection(), $this->getTableName())
            )
        );
    }
}
