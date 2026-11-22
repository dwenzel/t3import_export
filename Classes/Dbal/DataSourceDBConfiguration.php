<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Dbal;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use CPSIT\ImportExportCore\Dbal\DataSourceDBAL;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Translates the configuration of `DataSourceDB` (before 4.0) into the
 * configuration of `DataSourceDBAL` of the core.
 *
 * Comma-separated `fields` and `orderBy` with plain column names and a
 * numeric `limit` are converted. SQL in the options (a `where` string,
 * `join`, `groupBy`, `alias`, expressions or qualified names in `fields` and
 * `orderBy`, a table name that is not a plain name) cannot be expressed with
 * the options of the core; such a configuration is read by the SQL fallback
 * of `DataSourceDB`. `fallbackOptions()` names these options.
 *
 * Empty values are ignored, as before 4.0 (e.g. `where: ''`, `limit: 0`).
 *
 * @internal
 */
final class DataSourceDBConfiguration
{
    public const string KEY_ALIAS = 'alias';
    public const string KEY_GROUP_BY = 'groupBy';
    public const string KEY_JOIN = 'join';
    public const string KEY_JOIN_CONDITION = 'condition';
    public const string KEY_TYPE = 'type';
    public const string KEY_LEFT_JOIN = 'leftJoin';

    /**
     * Options of the core that cannot be combined with the SQL fallback
     */
    public const array CORE_ONLY_KEYS = [
        DataSourceDBAL::KEY_FILTER,
        DataSourceDBAL::KEY_TIMEZONE,
        DataSourceDBAL::KEY_CURSOR_FIELD,
        DataSourceDBAL::KEY_PAGE_SIZE,
    ];

    /**
     * Options that were accepted but had no effect before 4.0: `type` (the
     * source always used a SELECT query) and `leftJoin` (only read by
     * `LookUpDB`).
     */
    public const array IGNORED_KEYS = [
        self::KEY_TYPE,
        self::KEY_LEFT_JOIN,
    ];

    private const string COLUMN_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * Returns the names of the options that hold SQL and need the fallback.
     * An empty list means the configuration can be read by the core.
     *
     * @param array<array-key, mixed> $configuration
     * @return list<string>
     */
    public static function fallbackOptions(array $configuration): array
    {
        $options = [];
        $table = $configuration[DataSourceDBAL::KEY_TABLE] ?? null;
        if (\is_string($table) && !self::isTable($table)) {
            $options[] = DataSourceDBAL::KEY_TABLE;
        }
        if (!empty($configuration[self::KEY_ALIAS])) {
            $options[] = self::KEY_ALIAS;
        }
        $where = $configuration[DataSourceDBAL::KEY_WHERE] ?? null;
        if (!empty($where) && !\is_array($where)) {
            $options[] = DataSourceDBAL::KEY_WHERE;
        }
        if (self::hasJoin($configuration)) {
            $options[] = self::KEY_JOIN;
        }
        if (!empty($configuration[self::KEY_GROUP_BY])) {
            $options[] = self::KEY_GROUP_BY;
        }
        $fields = $configuration[DataSourceDBAL::KEY_FIELDS] ?? null;
        if (\is_string($fields) && self::fieldList($fields) === null) {
            $options[] = DataSourceDBAL::KEY_FIELDS;
        }
        $orderBy = $configuration[DataSourceDBAL::KEY_ORDER_BY] ?? null;
        if (\is_string($orderBy) && self::orderByMap($orderBy) === null) {
            $options[] = DataSourceDBAL::KEY_ORDER_BY;
        }
        $limit = $configuration[DataSourceDBAL::KEY_LIMIT] ?? null;
        if (!empty($limit) && !\is_array($limit) && self::limit($limit) === null) {
            $options[] = DataSourceDBAL::KEY_LIMIT;
        }

        return $options;
    }

    /**
     * Converts a configuration without SQL (see `fallbackOptions()`) into the
     * configuration of the core.
     *
     * @param array<array-key, mixed> $configuration
     * @return array<array-key, mixed>
     */
    public static function toCore(array $configuration): array
    {
        foreach ([...self::IGNORED_KEYS, self::KEY_ALIAS, self::KEY_JOIN, self::KEY_GROUP_BY] as $key) {
            unset($configuration[$key]);
        }
        foreach ([DataSourceDBAL::KEY_WHERE, DataSourceDBAL::KEY_FIELDS, DataSourceDBAL::KEY_ORDER_BY, DataSourceDBAL::KEY_LIMIT] as $key) {
            if (\array_key_exists($key, $configuration) && empty($configuration[$key])) {
                unset($configuration[$key]);
            }
        }
        $fields = $configuration[DataSourceDBAL::KEY_FIELDS] ?? null;
        if (\is_string($fields)) {
            $list = self::fieldList($fields) ?? [];
            if ($list === []) {
                unset($configuration[DataSourceDBAL::KEY_FIELDS]);
            } else {
                $configuration[DataSourceDBAL::KEY_FIELDS] = $list;
            }
        }
        $orderBy = $configuration[DataSourceDBAL::KEY_ORDER_BY] ?? null;
        if (\is_string($orderBy)) {
            $configuration[DataSourceDBAL::KEY_ORDER_BY] = self::orderByMap($orderBy) ?? [];
        }
        $limit = $configuration[DataSourceDBAL::KEY_LIMIT] ?? null;
        if ($limit !== null && !\is_array($limit)) {
            $configuration[DataSourceDBAL::KEY_LIMIT] = self::limit($limit) ?? $limit;
        }

        return $configuration;
    }

    /**
     * Whether a `join` is configured; before 4.0 a join without table was
     * ignored.
     *
     * @param array<array-key, mixed> $configuration
     */
    public static function hasJoin(array $configuration): bool
    {
        $join = $configuration[self::KEY_JOIN] ?? null;

        return \is_array($join) && !empty($join[DataSourceDBAL::KEY_TABLE]);
    }

    /**
     * Splits comma-separated fields into column names, as before 4.0. `*`
     * stands for all fields (empty list). Returns null if a field is not a
     * plain column name (e.g. `p.uid`, `uid AS id`, `COUNT(*)`).
     *
     * @return list<string>|null
     */
    public static function fieldList(string $fields): ?array
    {
        $list = GeneralUtility::trimExplode(',', $fields, true);
        if ($list === [] || $list === ['*']) {
            return [];
        }
        foreach ($list as $field) {
            if (!self::isColumn($field)) {
                return null;
            }
        }

        return $list;
    }

    /**
     * Converts a comma-separated sort order (`title DESC, uid`) into a map,
     * as before 4.0: a field is sorted descending if it is followed by `desc`
     * (any case), otherwise ascending; further words are ignored. Returns null
     * if a field is not a plain column name.
     *
     * @return array<string, string>|null
     */
    public static function orderByMap(string $orderBy): ?array
    {
        $map = [];
        foreach (GeneralUtility::trimExplode(',', $orderBy, true) as $item) {
            $words = GeneralUtility::trimExplode(' ', $item, true);
            $field = $words[0];
            if (!self::isColumn($field)) {
                return null;
            }
            $direction = isset($words[1]) && strtolower($words[1]) === 'desc' ? 'DESC' : 'ASC';
            // the first sort order of a field decides, as in SQL
            $map[$field] ??= $direction;
        }

        return $map;
    }

    /**
     * Returns a positive limit as integer, or null if the value is not a
     * positive integer.
     */
    public static function limit(mixed $limit): ?int
    {
        if (!MathUtility::canBeInterpretedAsInteger($limit) || (int)$limit < 1) {
            return null;
        }

        return (int)$limit;
    }

    private static function isColumn(string $name): bool
    {
        return preg_match(self::COLUMN_PATTERN, $name) === 1;
    }

    private static function isTable(string $name): bool
    {
        $parts = explode('.', $name);

        return \count($parts) <= 2 && array_filter($parts, self::isColumn(...)) === $parts;
    }
}
