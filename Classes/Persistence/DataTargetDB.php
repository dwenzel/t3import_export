<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\Component\DisableableInterface;
use CPSIT\ImportExportCore\Dbal\DataTargetDBAL;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\PersistenceException;
use CPSIT\T3importExport\Component\DisableableTrait;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Copyright notice
 * (c) 2016. Vladimir Falcón Piva <falcon@cps-it.de>
 * All rights reserved
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Writes records into a database table.
 *
 * Adapter on `DataTargetDBAL` of the core, which accepts the options and the
 * records of earlier versions:
 *
 * - a record with a non-empty `__identity` updates the row with this `uid`
 *   (`__identity` -> `uid`); if there is no such row, nothing is written, as
 *   before 4.0. Other records are inserted.
 * - `unsetKeys` and `skip` are applied before the record is written
 * - without `connection`, the connection of the table mapping of TYPO3 is
 *   used
 * - a record with `__identity` and another `uid` changes the `uid` of the row
 *   with a fallback on the connection of TYPO3 (deprecated, removed in 5.0)
 *
 * The rows of a batch are written in one transaction, which is committed by
 * `flushBatch()` and `persistAll()` and rolled back by `fail()`.
 *
 * Neither the core nor the fallback use the DataHandler: system fields such
 * as `tstamp`, `crdate` or `pid` are not set, as before 4.0.
 */
class DataTargetDB extends DataTargetDBAL implements DisableableInterface
{
    use DisableableTrait;

    /**
     * @deprecated since 4.0, not used any more. A connection that is not
     * configured throws a `MissingResourceException` (1795351767).
     */
    final public const string MISSING_CONNECTION_MESSAGE = 'Missing database connection for table "%s"';
    /**
     * @deprecated since 4.0, not used any more
     */
    final public const int MISSING_CONNECTION_CODE = 1_646_037_375;
    final public const string DEFAULT_IDENTITY_FIELD = '__identity';
    final public const string FIELD_TABLE = 'table';
    final public const string FIELD_FIELD = 'field';
    final public const string FIELD_SKIP = 'skip';
    final public const string FIELD_IF_EMPTY = 'ifEmpty';
    final public const string FIELD_IF_NOT_EMPTY = 'ifNotEmpty';
    final public const string FIELD_UNSET_KEYS = 'unsetKeys';
    final public const string FIELD_IDENTIFIER = 'identifier';
    final public const string UID_FIELD = 'uid';
    final public const int PERSISTENCE_EXCEPTION_CODE = 1_647_701_464;
    final public const int INVALID_CONFIGURATION_EXCEPTION_CODE = 1_796_600_001;

    /**
     * `uid` of the row that the current record updates: the record has an
     * `__identity`.
     */
    private ?string $identity = null;

    public function __construct(protected readonly Typo3ConnectionProvider $typo3ConnectionProvider)
    {
        parent::__construct($typo3ConnectionProvider);
    }

    /**
     * Tells if the configuration is valid: the options of earlier versions
     * and those of the core (`table` must be a plain name, `connection`,
     * `key` and `idField`).
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (!isset($configuration[self::FIELD_TABLE])) {
            return false;
        }

        if (isset($configuration[self::FIELD_UNSET_KEYS])
            && !is_string($configuration[self::FIELD_UNSET_KEYS])) {
            return false;
        }

        if (isset($configuration[self::FIELD_SKIP])
            && (!is_array($configuration[self::FIELD_SKIP])
                || empty($configuration[self::FIELD_SKIP]))
        ) {
            return false;
        }

        foreach ([self::FIELD_IF_EMPTY, self::FIELD_IF_NOT_EMPTY] as $condition) {
            if (
                isset($configuration[self::FIELD_SKIP][$condition])
                && (
                    !is_array($configuration[self::FIELD_SKIP][$condition])
                    || empty($configuration[self::FIELD_SKIP][$condition])
                    || !is_string($configuration[self::FIELD_SKIP][$condition][self::FIELD_FIELD] ?? null)
                    || empty($configuration[self::FIELD_SKIP][$condition][self::FIELD_FIELD])
                )
            ) {
                return false;
            }
        }

        return parent::isConfigurationValid($configuration);
    }

    /**
     * Writes a record into the table
     *
     * A record with a non-empty `__identity` updates the row with this `uid`;
     * other records are inserted. Keys in `unsetKeys` are removed first.
     *
     * @param array<array-key, mixed>|object $object
     * @param array<array-key, mixed>|null $configuration
     * @return bool false if the record was skipped (`skip`), otherwise true
     * @throws InvalidConfigurationException if the configuration is invalid
     * @throws PersistenceException if the record cannot be written
     */
    public function persist(array|object $object, ?array $configuration = null): bool
    {
        $configuration ??= $this->configuration;
        if (!$this->isConfigurationValid($configuration)) {
            throw new InvalidConfigurationException(
                'Invalid configuration of DataTargetDB.',
                self::INVALID_CONFIGURATION_EXCEPTION_CODE
            );
        }
        if (!\is_array($object)) {
            // the core rejects objects, as the target did before 4.0
            return parent::persist($object, $configuration);
        }
        if ($this->shouldSkip($object, $configuration)) {
            return false;
        }
        if (isset($configuration[self::FIELD_IDENTIFIER])) {
            DeprecationUtility::trigger(
                'The option "identifier" of DataTargetDB has no effect and will not be accepted in 5.0. Use the option "connection" to select a database connection.',
                DeprecationUtility::DATABASE_ISSUE_URL
            );
        }

        $object = $this->unsetKeys($object, $configuration);
        $record = $object;
        unset($record[self::DEFAULT_IDENTITY_FIELD]);
        $identity = empty($object[self::DEFAULT_IDENTITY_FIELD]) ? null : $object[self::DEFAULT_IDENTITY_FIELD];
        $configuration[self::KEY_CONNECTION] = $this->getConnectionName($configuration);
        $configuration[self::KEY_ID_FIELD] ??= self::UID_FIELD;
        // a connection that is not configured is a configuration error, not
        // an error of the record
        $this->typo3ConnectionProvider->getConnection($configuration[self::KEY_CONNECTION]);

        try {
            if ($identity === null) {
                parent::persist($record, $configuration);
            } elseif ($this->changesUid($record, $identity)) {
                $this->changeUidWithFallback($record, $identity, $configuration);
            } else {
                // values that are not scalar are rejected by the core
                if (!\array_key_exists(self::UID_FIELD, $record)) {
                    $record[self::UID_FIELD] = $identity;
                }
                $configuration[self::KEY_KEY] = [self::UID_FIELD];
                $this->identity = \is_scalar($identity) ? (string)$identity : null;
                parent::persist($record, $configuration);
            }
        } catch (\Exception $exception) {
            throw new PersistenceException(
                ($identity === null ? 'Insert' : 'Update') . ' Exception:' . PHP_EOL
                . 'Data:' . PHP_EOL
                . json_encode($object, JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL
                . $exception->getMessage() . PHP_EOL,
                self::PERSISTENCE_EXCEPTION_CODE,
                $exception
            );
        } finally {
            $this->identity = null;
        }
        // the core finds the records of the batch by the record it was given
        $last = array_key_last($this->written);
        if ($last !== null && $this->written[$last][0] === $record) {
            $this->written[$last][0] = $object;
        }

        return true;
    }

    /**
     * Whether the record has a `uid` other than its `__identity`
     *
     * @param array<array-key, mixed> $record without `__identity`
     */
    protected function changesUid(array $record, mixed $identity): bool
    {
        if (!\is_scalar($identity) || !\array_key_exists(self::UID_FIELD, $record)) {
            return false;
        }
        $uid = $record[self::UID_FIELD];

        return $uid === null || (\is_scalar($uid) && (string)$uid !== (string)$identity);
    }

    /**
     * A record with `__identity` updates the row with this `uid`, without
     * looking it up first: if there is no such row, nothing is written, as
     * before 4.0.
     */
    protected function findExisting(Connection $connection, string $table, string $idField, array $conditions): ?string
    {
        if ($this->identity !== null) {
            return $this->identity;
        }

        return parent::findExisting($connection, $table, $idField, $conditions);
    }

    /**
     * Returns the name of the connection: `connection` of the configuration
     * or the connection of the table mapping of TYPO3.
     *
     * @param array<array-key, mixed> $configuration
     */
    protected function getConnectionName(array $configuration): string
    {
        $connection = $configuration[self::KEY_CONNECTION] ?? null;
        if (\is_string($connection) && $connection !== '') {
            return $connection;
        }
        return $this->typo3ConnectionProvider->getConnectionNameForTable((string)$configuration[self::KEY_TABLE]);
    }

    /**
     * Updates the row `__identity` and sets its `uid` to the one of the record
     * with the connection of TYPO3, as before 4.0. The core cannot change the
     * field it identifies a row by. The row is written in the transaction of
     * the current batch.
     *
     * @deprecated since 4.0, will be removed in 5.0
     * @param array<array-key, mixed> $record
     * @param array<array-key, mixed> $configuration
     */
    protected function changeUidWithFallback(array $record, mixed $identity, array $configuration): void
    {
        DeprecationUtility::trigger(
            'Changing the uid of a row with "__identity" and another "uid" in a record for DataTargetDB is deprecated since 4.0 and will not be possible in 5.0.',
            DeprecationUtility::DATABASE_ISSUE_URL
        );
        /** @var string $connectionName */
        $connectionName = $configuration[self::KEY_CONNECTION];
        $connection = $this->connectionProvider->getConnection($connectionName);
        if ($this->batchFlushed) {
            $this->written = [];
            $this->batchFlushed = false;
        }
        if (!isset($this->transactions[$connectionName])) {
            $connection->beginTransaction();
            $this->transactions[$connectionName] = $connection;
        }
        $connection->update((string)$configuration[self::KEY_TABLE], $record, [self::UID_FIELD => $identity]);
        $this->written[] = [$record, (string)$record[self::UID_FIELD]];
    }

    /**
     * @param array<array-key, mixed> $record
     * @param array<array-key, mixed> $configuration
     * @return array<array-key, mixed>
     */
    protected function unsetKeys(array $record, array $configuration): array
    {
        if (!isset($configuration[self::FIELD_UNSET_KEYS])) {
            return $record;
        }
        foreach (GeneralUtility::trimExplode(',', (string)$configuration[self::FIELD_UNSET_KEYS], true) as $key) {
            unset($record[$key]);
        }

        return $record;
    }

    /**
     * Tells if the record should be skipped, i.e. not be persisted
     */
    protected function shouldSkip(array $record, array $configuration): bool
    {
        $ifNotEmptyPath = implode('/', [self::FIELD_SKIP, self::FIELD_IF_NOT_EMPTY, self::FIELD_FIELD]);
        $ifEmptyPath = implode('/', [self::FIELD_SKIP, self::FIELD_IF_EMPTY, self::FIELD_FIELD]);
        if (ArrayUtility::isValidPath($configuration, $ifNotEmptyPath)) {
            $fieldName = ArrayUtility::getValueByPath($configuration, $ifNotEmptyPath);
            return !empty($record[$fieldName]);
        }

        if (ArrayUtility::isValidPath($configuration, $ifEmptyPath)) {
            $fieldName = ArrayUtility::getValueByPath($configuration, $ifEmptyPath);
            return empty($record[$fieldName]);
        }
        return false;
    }
}
