<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use CPSIT\ImportExportCore\Component\DisableableInterface;
use CPSIT\ImportExportCore\Dbal\DataTargetDBAL;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Exception\PersistenceException;
use CPSIT\ImportExportCore\Persistence\BatchAwareInterface;
use CPSIT\ImportExportCore\Persistence\FailureAwareInterface;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Test for DataTargetDB
 *
 * The rows written by the adapter are compared with the rows written before
 * 4.0 in Tests/Functional/Persistence/DataTargetDBAdapterTest, which also
 * covers the fallback (it uses the connection of TYPO3 with its caches).
 */
#[CoversClass(DataTargetDB::class)]
class DataTargetDBTest extends TestCase
{
    final public const array VALID_CONFIG_EMPTY_FIELD = [
        DataTargetDB::FIELD_TABLE => 'foo',
        DataTargetDB::FIELD_SKIP => [
            DataTargetDB::FIELD_IF_EMPTY => [
                DataTargetDB::FIELD_FIELD => 'bar',
            ],
        ],
    ];

    final public const array VALID_CONFIG_NOT_EMPTY_FIELD = [
        DataTargetDB::FIELD_TABLE => 'foo',
        DataTargetDB::FIELD_SKIP => [
            DataTargetDB::FIELD_IF_NOT_EMPTY => [
                DataTargetDB::FIELD_FIELD => 'bar',
            ],
        ],
    ];

    protected DataTargetDB $subject;

    /**
     * @var array<string, Connection>
     */
    private array $connections = [];

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    protected function setUp(): void
    {
        DeprecationUtility::reset();
        $GLOBALS['TYPO3_CONF_VARS']['DB'] = [
            'Connections' => [
                'Default' => ['driver' => 'pdo_sqlite'],
                'Legacy' => ['driver' => 'pdo_sqlite'],
            ],
            'TableMapping' => [
                'tx_legacy_record' => 'Legacy',
            ],
        ];
        $this->connections = [
            'Default' => $this->createConnection(),
            'Legacy' => $this->createConnection(),
        ];
        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getConnectionByName')
            ->willReturnCallback(fn(string $name): Connection => $this->connections[$name]);
        $this->subject = new DataTargetDB(new Typo3ConnectionProvider($connectionPool));
        set_error_handler(function (int $errno, string $message): bool {
            $this->deprecations[] = $message;
            return true;
        }, E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
        unset($GLOBALS['TYPO3_CONF_VARS']['DB']);
    }

    /**
     * Creates a connection to a database in memory with the tables `foo` and
     * `tx_legacy_record`, each with one row (uid 1).
     */
    private function createConnection(): Connection
    {
        /** @var Connection $connection */
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'wrapperClass' => Connection::class,
        ]);
        foreach (['foo', 'tx_legacy_record'] as $table) {
            $connection->executeStatement(
                'CREATE TABLE ' . $table . ' (uid INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER NOT NULL DEFAULT 0, title VARCHAR(255) NOT NULL DEFAULT \'\', bar VARCHAR(255) NOT NULL DEFAULT \'\', baz VARCHAR(255) NOT NULL DEFAULT \'\', tstamp INTEGER NOT NULL DEFAULT 0, crdate INTEGER NOT NULL DEFAULT 0)'
            );
            $connection->executeStatement('INSERT INTO ' . $table . ' (uid, pid, title) VALUES (1, 1, \'first\')');
        }

        return $connection;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $connection = 'Default', string $table = 'foo'): array
    {
        return $this->connections[$connection]
            ->executeQuery('SELECT uid, pid, title, bar, baz, tstamp, crdate FROM ' . $table . ' ORDER BY uid')
            ->fetchAllAssociative();
    }

    /**
     * @return list<string>
     */
    private function titles(string $connection = 'Default', string $table = 'foo'): array
    {
        return array_column($this->rows($connection, $table), 'title');
    }

    #[Test]
    public function isDataTargetOfTheCoreAndDisableable(): void
    {
        self::assertInstanceOf(DataTargetDBAL::class, $this->subject);
        self::assertInstanceOf(DisableableInterface::class, $this->subject);
        self::assertInstanceOf(BatchAwareInterface::class, $this->subject);
        self::assertInstanceOf(FailureAwareInterface::class, $this->subject);
    }

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'missing field table' => [
                [],
            ],
            'table is not a plain name' => [
                [DataTargetDB::FIELD_TABLE => 'foo-bar'],
            ],
            'table is not a string' => [
                [DataTargetDB::FIELD_TABLE => ['foo']],
            ],
            'connection is empty' => [
                [DataTargetDB::FIELD_TABLE => 'foo', 'connection' => ''],
            ],
            'key is not a list of fields' => [
                [DataTargetDB::FIELD_TABLE => 'foo', 'key' => 'import_id'],
            ],
            'idField is not a plain name' => [
                [DataTargetDB::FIELD_TABLE => 'foo', 'idField' => 'u id'],
            ],
            'field `unsetKeys` is not string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_UNSET_KEYS => [],
                ],
            ],
            'skip must not be string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => 'bar',
                ],
            ],
            'skip must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [],
                ],
            ],
            'ifEmpty must not be string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => 'baz',
                    ],
                ],
            ],
            'ifEmpty must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => [],
                    ],
                ],
            ],
            'ifNotEmpty must not be string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => 'baz',
                    ],
                ],
            ],
            'ifNotEmpty must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => [],
                    ],
                ],
            ],
            'ifEmpty.field must not be array' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => [
                            DataTargetDB::FIELD_FIELD => [],
                        ],
                    ],
                ],
            ],
            'ifEmpty.field must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => [
                            DataTargetDB::FIELD_FIELD => '',
                        ],
                    ],
                ],
            ],
            'ifNotEmpty.field must not be array' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => [
                            DataTargetDB::FIELD_FIELD => [],
                        ],
                    ],
                ],
            ],
            'ifNotEmpty.field must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => [
                            DataTargetDB::FIELD_FIELD => '',
                        ],
                    ],
                ],
            ],

        ];
    }

    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function isConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function persistThrowsExceptionForInvalidConfiguration(array $configuration): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(DataTargetDB::INVALID_CONFIGURATION_EXCEPTION_CODE);

        $this->subject->persist(['title' => 'new'], $configuration);
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'minimal config' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                ],
            ],
            'table + unsetKeys ' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_UNSET_KEYS => 'bar,baz',
                ],
            ],
            'skip if field `bar` is empty' => [
                self::VALID_CONFIG_EMPTY_FIELD,
            ],
            'skip if field `bar` is not empty' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
            ],
            'options of the core' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    'connection' => 'Legacy',
                    'key' => ['import_id'],
                    'idField' => 'uid',
                ],
            ],
            'options that had no effect before 4.0' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    'identifier' => 'bar',
                    'pid' => 123,
                    'mode' => 'insert',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('validConfigurationDataProvider')]
    public function isConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        self::assertTrue($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function persistInsertsRecordWithoutIdentity(): void
    {
        $result = $this->subject->persist(['pid' => 2, 'title' => 'new'], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertTrue($result);
        self::assertSame(
            [
                ['uid' => 1, 'pid' => 1, 'title' => 'first', 'bar' => '', 'baz' => '', 'tstamp' => 0, 'crdate' => 0],
                ['uid' => 2, 'pid' => 2, 'title' => 'new', 'bar' => '', 'baz' => '', 'tstamp' => 0, 'crdate' => 0],
            ],
            $this->rows()
        );
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyIdentityDataProvider(): array
    {
        return [
            'empty string' => [''],
            'zero' => [0],
            'zero as string' => ['0'],
            'null' => [null],
        ];
    }

    #[Test]
    #[DataProvider('emptyIdentityDataProvider')]
    public function persistInsertsRecordWithEmptyIdentityWithoutIt(mixed $identity): void
    {
        $result = $this->subject->persist(['__identity' => $identity, 'title' => 'new'], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertTrue($result);
        self::assertSame(['first', 'new'], $this->titles());
    }

    #[Test]
    public function persistUpdatesRowWithUidOfIdentity(): void
    {
        $result = $this->subject->persist(['__identity' => 1, 'title' => 'updated'], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertTrue($result);
        self::assertSame(
            [['uid' => 1, 'pid' => 1, 'title' => 'updated', 'bar' => '', 'baz' => '', 'tstamp' => 0, 'crdate' => 0]],
            $this->rows()
        );
    }

    #[Test]
    public function persistUpdatesRowWithIdentityAsString(): void
    {
        $this->subject->persist(['__identity' => '1', 'title' => 'updated'], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertSame(['updated'], $this->titles());
    }

    #[Test]
    public function persistWritesNothingForIdentityWithoutRow(): void
    {
        $result = $this->subject->persist(['__identity' => 99, 'title' => 'missing'], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertTrue($result);
        self::assertSame(['first'], $this->titles());
    }

    #[Test]
    public function persistUpdatesRowWithIdentityAndSameUid(): void
    {
        $this->subject->persist(['__identity' => 1, 'uid' => '1', 'title' => 'updated'], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertSame([1], array_column($this->rows(), 'uid'));
        self::assertSame(['updated'], $this->titles());
        self::assertSame([], $this->deprecations);
    }

    /**
     * Before 4.0, the update without fields failed with an SQL error.
     */
    #[Test]
    public function persistAcceptsRecordWithIdentityOnly(): void
    {
        self::assertTrue($this->subject->persist(['__identity' => 1], ['table' => 'foo']));
        $this->subject->persistAll();

        self::assertSame(['first'], $this->titles());
    }

    /**
     * Before 4.0, false was written as empty string, which MySQL rejects for
     * an integer column.
     */
    #[Test]
    public function persistWritesBooleansAsIntegers(): void
    {
        $this->subject->persist(['pid' => false, 'title' => 'new'], ['table' => 'foo']);
        $this->subject->persist(['__identity' => 1, 'pid' => true], ['table' => 'foo']);
        $this->subject->persistAll();

        self::assertSame([1, 0], array_column($this->rows(), 'pid'));
    }

    #[Test]
    public function persistDoesNotSetSystemFields(): void
    {
        $this->subject->persist(['title' => 'new'], ['table' => 'foo']);
        $this->subject->persist(['__identity' => 1, 'title' => 'updated'], ['table' => 'foo']);
        $this->subject->persistAll();

        foreach ($this->rows() as $row) {
            self::assertSame(0, $row['tstamp']);
            self::assertSame(0, $row['crdate']);
        }
        self::assertSame(0, $this->rows()[1]['pid']);
    }

    #[Test]
    public function persistUnsetsConfiguredKeys(): void
    {
        $configuration = [
            DataTargetDB::FIELD_TABLE => 'foo',
            DataTargetDB::FIELD_UNSET_KEYS => 'unknown, other',
        ];

        $this->subject->persist(['title' => 'new', 'unknown' => 'x', 'other' => 'y'], $configuration);
        $this->subject->persistAll();

        self::assertSame(['first', 'new'], $this->titles());
    }

    #[Test]
    public function persistCanUnsetIdentity(): void
    {
        $configuration = [
            DataTargetDB::FIELD_TABLE => 'foo',
            DataTargetDB::FIELD_UNSET_KEYS => '__identity',
        ];

        $this->subject->persist(['__identity' => 1, 'title' => 'new'], $configuration);
        $this->subject->persistAll();

        self::assertSame(['first', 'new'], $this->titles());
    }

    public static function skipIfDataProvider(): array
    {
        return [
            // $configuration, $record
            'skip b/c field `bar` is empty string' => [
                self::VALID_CONFIG_EMPTY_FIELD,
                ['bar' => ''],
            ],
            'skip b/c field `bar` is empty array' => [
                self::VALID_CONFIG_EMPTY_FIELD,
                ['bar' => []],
            ],
            'skip b/c field `bar` is not empty string' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
                ['bar' => 'lala'],
            ],
            'skip b/c field `bar` is not empty array' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
                ['bar' => ['baz']],
            ],
            'skip b/c field `bar` is not empty but float' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
                ['bar' => 3.12],
            ],
        ];
    }

    /**
     * @throws InvalidConfigurationException
     */
    #[Test]
    #[DataProvider('skipIfDataProvider')]
    public function persistSkipsRecordMatchingCondition(array $configuration, array $record): void
    {
        self::assertFalse($this->subject->persist($record, $configuration));
        self::assertFalse($this->connections['Default']->isTransactionActive());
    }

    #[Test]
    public function persistUsesConfigurationOfComponentWithoutConfiguration(): void
    {
        $this->subject->setConfiguration(['table' => 'foo']);

        $this->subject->persist(['title' => 'new']);
        $this->subject->persistAll();

        self::assertSame(['first', 'new'], $this->titles());
    }

    #[Test]
    public function persistUsesConnectionOfTableMapping(): void
    {
        $this->subject->persist(['title' => 'legacy'], ['table' => 'tx_legacy_record']);
        $this->subject->persistAll();

        self::assertSame(['first', 'legacy'], $this->titles('Legacy', 'tx_legacy_record'));
        self::assertSame(['first'], $this->titles('Default', 'tx_legacy_record'));
    }

    #[Test]
    public function persistUsesConfiguredConnection(): void
    {
        $this->subject->persist(['title' => 'legacy'], ['table' => 'foo', 'connection' => 'Legacy']);
        $this->subject->persistAll();

        self::assertSame(['first', 'legacy'], $this->titles('Legacy'));
        self::assertSame(['first'], $this->titles('Default'));
    }

    #[Test]
    public function persistThrowsExceptionForConnectionThatIsNotConfigured(): void
    {
        $this->expectException(MissingResourceException::class);

        $this->subject->persist(['title' => 'new'], ['table' => 'foo', 'connection' => 'Missing']);
    }

    #[Test]
    public function identifierHasNoEffectAndIsDeprecated(): void
    {
        $this->subject->persist(['title' => 'new'], ['table' => 'foo', 'identifier' => 'Legacy']);
        $this->subject->persistAll();

        self::assertSame(['first', 'new'], $this->titles('Default'));
        self::assertSame(['first'], $this->titles('Legacy'));
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('"identifier" of DataTargetDB has no effect', $this->deprecations[0]);
    }

    #[Test]
    public function keyOfTheCoreUpdatesRowsWithoutIdentity(): void
    {
        $configuration = ['table' => 'foo', 'key' => ['title']];

        $this->subject->persist(['title' => 'first', 'bar' => 'updated'], $configuration);
        $this->subject->persist(['title' => 'other', 'bar' => 'inserted'], $configuration);
        $this->subject->persistAll();

        self::assertSame(['updated', 'inserted'], array_column($this->rows(), 'bar'));
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function invalidRecordDataProvider(): array
    {
        return [
            'unknown column' => [['title' => 'new', 'unknown' => 'x']],
            'column that is not a plain name' => [['title' => 'new', 'foo-bar' => 'x']],
            'numeric key' => [['title' => 'new', 0 => 'x']],
            'array value' => [['title' => ['new']]],
            'object value' => [['title' => new \DateTimeImmutable()]],
            'empty record' => [[]],
            'update of unknown column' => [['__identity' => 1, 'unknown' => 'x']],
            '__identity that is not scalar' => [['__identity' => [1], 'title' => 'x']],
            'uid that is not scalar' => [['__identity' => 1, 'uid' => [1], 'title' => 'x']],
        ];
    }

    /**
     * @param array<array-key, mixed> $record
     */
    #[Test]
    #[DataProvider('invalidRecordDataProvider')]
    public function persistThrowsPersistenceExceptionForRecordThatCannotBeWritten(array $record): void
    {
        try {
            $this->subject->persist($record, ['table' => 'foo']);
            self::fail('An exception should be thrown.');
        } catch (PersistenceException $exception) {
            self::assertSame(DataTargetDB::PERSISTENCE_EXCEPTION_CODE, $exception->getCode());
            self::assertNotNull($exception->getPrevious());
        }
    }

    #[Test]
    public function persistRejectsObjects(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->subject->persist(new \stdClass(), ['table' => 'foo']);
    }

    #[Test]
    public function rowsOfABatchAreWrittenInATransaction(): void
    {
        $this->subject->persist(['title' => 'new'], ['table' => 'foo']);

        self::assertTrue($this->connections['Default']->isTransactionActive());

        $this->subject->flushBatch(new TaskResult());

        self::assertFalse($this->connections['Default']->isTransactionActive());
        self::assertSame(['first', 'new'], $this->titles());
    }

    #[Test]
    public function failRollsBackTheRowsOfTheCurrentBatch(): void
    {
        $this->subject->persist(['title' => 'committed'], ['table' => 'foo']);
        $this->subject->flushBatch(new TaskResult());
        $this->subject->persist(['title' => 'new'], ['table' => 'foo']);
        $this->subject->persist(['__identity' => 1, 'title' => 'updated'], ['table' => 'foo']);

        $this->subject->fail(new \RuntimeException('failed'));

        self::assertFalse($this->connections['Default']->isTransactionActive());
        self::assertSame(['first', 'committed'], $this->titles());
    }

    #[Test]
    public function secondFailAndPersistAllAfterFailDoNothing(): void
    {
        $this->subject->persist(['title' => 'new'], ['table' => 'foo']);
        $this->subject->fail(new \RuntimeException('failed'));

        $this->subject->fail(new \RuntimeException('failed again'));
        $this->subject->persistAll();
        $this->subject->flushBatch(new TaskResult());

        self::assertFalse($this->connections['Default']->isTransactionActive());
        self::assertSame(['first'], $this->titles());
    }

    #[Test]
    public function secondPersistAllDoesNothing(): void
    {
        $this->subject->persist(['title' => 'new'], ['table' => 'foo']);
        $this->subject->persistAll();

        $this->subject->persistAll();
        $this->subject->fail(new \RuntimeException('failed'));

        self::assertSame(['first', 'new'], $this->titles());
    }

    #[Test]
    public function failRollsBackTheTransactionsOfAllConnections(): void
    {
        $this->subject->persist(['title' => 'new'], ['table' => 'foo']);
        $this->subject->persist(['title' => 'new'], ['table' => 'tx_legacy_record']);

        $this->subject->fail(new \RuntimeException('failed'));

        self::assertSame(['first'], $this->titles('Default'));
        self::assertSame(['first'], $this->titles('Legacy', 'tx_legacy_record'));
    }

    #[Test]
    public function getTargetIdReturnsUidOfInsertedAndUpdatedRecords(): void
    {
        $inserted = ['title' => 'new'];
        $updated = ['__identity' => 1, 'title' => 'updated'];

        $this->subject->persist($inserted, ['table' => 'foo']);
        $this->subject->persist($updated, ['table' => 'foo']);

        self::assertSame('2', $this->subject->getTargetId($inserted));
        self::assertSame('1', $this->subject->getTargetId($updated));
    }

    #[Test]
    public function isDisabledEvaluatesDisable(): void
    {
        self::assertTrue($this->subject->isDisabled(['disable' => 1]));
        self::assertFalse($this->subject->isDisabled(['table' => 'foo']));
    }
}
