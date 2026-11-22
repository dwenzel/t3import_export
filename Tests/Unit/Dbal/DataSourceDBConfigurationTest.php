<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Dbal;

use CPSIT\T3importExport\Dbal\DataSourceDBConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSourceDBConfiguration::class)]
class DataSourceDBConfigurationTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function coreConfigurationDataProvider(): array
    {
        return [
            'table only' => [
                ['table' => 'pages'],
                ['table' => 'pages'],
            ],
            'comma-separated fields' => [
                ['table' => 'pages', 'fields' => ' uid, title ,,pid'],
                ['table' => 'pages', 'fields' => ['uid', 'title', 'pid']],
            ],
            'all fields' => [
                ['table' => 'pages', 'fields' => '*'],
                ['table' => 'pages'],
            ],
            'empty values are ignored' => [
                ['table' => 'pages', 'fields' => '', 'where' => '', 'groupBy' => '', 'orderBy' => '', 'limit' => '0', 'alias' => '', 'join' => ['alias' => 'x']],
                ['table' => 'pages'],
            ],
            'sort order' => [
                ['table' => 'pages', 'orderBy' => 'title Desc, uid, pid asc, sorting foo'],
                ['table' => 'pages', 'orderBy' => ['title' => 'DESC', 'uid' => 'ASC', 'pid' => 'ASC', 'sorting' => 'ASC']],
            ],
            'first sort order of a field' => [
                ['table' => 'pages', 'orderBy' => 'title desc, title asc'],
                ['table' => 'pages', 'orderBy' => ['title' => 'DESC']],
            ],
            'limit as string' => [
                ['table' => 'pages', 'limit' => '5'],
                ['table' => 'pages', 'limit' => 5],
            ],
            'limit as integer' => [
                ['table' => 'pages', 'limit' => 5],
                ['table' => 'pages', 'limit' => 5],
            ],
            'ignored options' => [
                ['table' => 'pages', 'type' => 'selectJoin', 'leftJoin' => ['table' => 'tt_content']],
                ['table' => 'pages'],
            ],
            'options of the core are kept' => [
                ['table' => 'pages', 'fields' => ['uid'], 'where' => ['pid' => 1], 'filter' => ['field' => 'uid', 'op' => '>', 'value' => 1], 'orderBy' => ['uid' => 'DESC'], 'connection' => 'Legacy', 'cursorField' => 'uid'],
                ['table' => 'pages', 'fields' => ['uid'], 'where' => ['pid' => 1], 'filter' => ['field' => 'uid', 'op' => '>', 'value' => 1], 'orderBy' => ['uid' => 'DESC'], 'connection' => 'Legacy', 'cursorField' => 'uid'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $expected
     */
    #[Test]
    #[DataProvider('coreConfigurationDataProvider')]
    public function configurationWithoutSqlIsConvertedForTheCore(array $configuration, array $expected): void
    {
        self::assertSame([], DataSourceDBConfiguration::fallbackOptions($configuration));
        self::assertSame($expected, DataSourceDBConfiguration::toCore($configuration));
    }

    /**
     * @return array<string, array{array<string, mixed>, list<string>}>
     */
    public static function fallbackDataProvider(): array
    {
        return [
            'where string' => [['table' => 'pages', 'where' => 'pid = 1'], ['where']],
            'join' => [['table' => 'pages', 'join' => ['table' => 'pages', 'alias' => 'p', 'condition' => 'p.uid = pages.pid']], ['join']],
            'group by' => [['table' => 'pages', 'groupBy' => 'pid'], ['groupBy']],
            'alias' => [['table' => 'pages', 'alias' => 'p'], ['alias']],
            'qualified field' => [['table' => 'pages', 'fields' => 'pages.uid'], ['fields']],
            'field with alias' => [['table' => 'pages', 'fields' => 'uid AS id'], ['fields']],
            'function in fields' => [['table' => 'pages', 'fields' => 'COUNT(*)'], ['fields']],
            'all fields and one field' => [['table' => 'pages', 'fields' => '*, uid'], ['fields']],
            'qualified sort field' => [['table' => 'pages', 'orderBy' => 'pages.uid DESC'], ['orderBy']],
            'limit with offset' => [['table' => 'pages', 'limit' => '10, 20'], ['limit']],
            'negative limit' => [['table' => 'pages', 'limit' => -1], ['limit']],
            'table with quotes' => [['table' => '`pages`'], ['table']],
            'several options' => [
                ['table' => 'pages', 'alias' => 'p', 'fields' => 'p.uid', 'where' => 'p.pid = 1', 'groupBy' => 'p.uid'],
                ['alias', 'where', 'groupBy', 'fields'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('fallbackDataProvider')]
    public function fallbackOptionsNameTheOptionsWithSql(array $configuration, array $expected): void
    {
        self::assertSame($expected, DataSourceDBConfiguration::fallbackOptions($configuration));
    }

    #[Test]
    public function tableWithSchemaDoesNotNeedTheFallback(): void
    {
        self::assertSame([], DataSourceDBConfiguration::fallbackOptions(['table' => 'legacy.pages']));
    }

    #[Test]
    public function joinWithoutTableIsIgnored(): void
    {
        self::assertFalse(DataSourceDBConfiguration::hasJoin(['table' => 'pages', 'join' => ['alias' => 'p']]));
        self::assertFalse(DataSourceDBConfiguration::hasJoin(['table' => 'pages', 'join' => 'pages']));
        self::assertTrue(DataSourceDBConfiguration::hasJoin(['table' => 'pages', 'join' => ['table' => 'pages']]));
    }
}
