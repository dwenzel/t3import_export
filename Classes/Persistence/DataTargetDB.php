<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\Component\AbstractComponent;
use CPSIT\ImportExportCore\Component\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Exception\PersistenceException;
use CPSIT\T3importExport\InvalidConfigurationException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

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
 * Class DataTargetDB
 * Persists records into a database.
 */
class DataTargetDB extends AbstractComponent implements DataTargetInterface, ConfigurableInterface
{
    use ConfigurableTrait;
    use DatabaseTrait;

    final public const string MISSING_CONNECTION_MESSAGE = 'Missing database connection for table "%s"';
    final public const int MISSING_CONNECTION_CODE = 1_646_037_375;
    final public const string DEFAULT_IDENTITY_FIELD = '__identity';
    final public const string FIELD_TABLE = 'table';
    final public const string FIELD_FIELD = 'field';
    final public const string FIELD_SKIP = 'skip';
    final public const string FIELD_IF_EMPTY = 'ifEmpty';
    final public const string FIELD_IF_NOT_EMPTY = 'ifNotEmpty';
    final public const string FIELD_UNSET_KEYS = 'unsetKeys';

    /**
     * Tells if the configuration is valid
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

        if (
            isset($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY])
            && (
                !is_array($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY])
                || empty($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY])
                || !is_string($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY][self::FIELD_FIELD])
                || empty($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY][self::FIELD_FIELD])
            )
        ) {
            return false;
        }

        if (
            isset($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY])
            && (
                !is_array($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY])
                || empty($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY])
                || !is_string($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY][self::FIELD_FIELD])
                || empty($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY][self::FIELD_FIELD])
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Persists an object into the database
     * If the record has a key '__identity' it will be
     * updated otherwise inserted as new record
     * If $configuration contains a key 'unsetKeys' it
     * will be handled as comma separated list of keys.
     * Any of those keys will be unset before persisting.
     *
     * @param array|DomainObjectInterface $object
     * @return bool
     * @throws InvalidConfigurationException
     */
    public function persist($object, ?array $configuration = null)
    {
        if ($this->shouldSkip($object, $configuration)) {
            return false;
        }
        $tableName = $configuration[self::FIELD_TABLE];

        /**
         * @todo We should respect the 'identifier' for additional (external) databases here
         * (without table mapping by core connection service)
         */
        $this->connection = $this->connectionPool->getConnectionForTable($tableName);
        if (!$this->connection instanceof Connection) {
            $message = sprintf(self::MISSING_CONNECTION_MESSAGE, $tableName);
            throw new InvalidConfigurationException(
                $message,
                self::MISSING_CONNECTION_CODE
            );
        }

        if (isset($configuration[self::FIELD_UNSET_KEYS])) {
            $unsetKeys = GeneralUtility::trimExplode(',', $configuration[self::FIELD_UNSET_KEYS], true);
            if ((bool)$unsetKeys) {
                foreach ($unsetKeys as $key) {
                    unset($object[$key]);
                }
            }
        }

        if (!empty($object[self::DEFAULT_IDENTITY_FIELD])) {
            $data = $object;
            $uid = $object[self::DEFAULT_IDENTITY_FIELD];
            unset($data[self::DEFAULT_IDENTITY_FIELD]);
            try {
                $this->connection->update(
                    $tableName,
                    $data,
                    ['uid' => $uid]
                );
            } catch (\Exception $exception) {
                $message = 'Update Exception:' . PHP_EOL;
                $message .= 'Data:' . PHP_EOL;
                $message .= json_encode($object, JSON_THROW_ON_ERROR);
                $message .= $exception->getMessage() . PHP_EOL;

                /**
                 * Fixme: write to log instead of catch and re-throw
                 */
                throw new PersistenceException(
                    $message,
                    1_647_701_464,
                    $exception
                );
            }

            return true;
        }

        if (empty($object[self::DEFAULT_IDENTITY_FIELD])) {
            unset($object[self::DEFAULT_IDENTITY_FIELD]);
        }

        try {
            $this->connection->insert(
                $tableName,
                $object
            );
        } catch (\Exception $exception) {
            $message = 'Insert Exception:' . PHP_EOL;
            $message .= 'Data:' . PHP_EOL;
            $message .= json_encode($object, JSON_THROW_ON_ERROR) . PHP_EOL;
            $message .= $exception->getMessage() . PHP_EOL;

            throw new PersistenceException(
                $message,
                1_647_701_464,
                $exception
            );
        }

        return true;
    }

    /**
     * Dummy method
     * Currently doesn't do anything
     *
     * @param null $result
     */
    public function persistAll($result = null, ?array $configuration = null) {}

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
