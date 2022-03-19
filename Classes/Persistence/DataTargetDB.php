<?php

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
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
 *
 * @package CPSIT\T3importExport\Persistence
 */
class DataTargetDB implements DataTargetInterface, ConfigurableInterface
{
    use ConfigurableTrait, DatabaseTrait;

    public const MISSING_CONNECTION_MESSAGE = 'Missing database connection for table "%s"';
    public const MISSING_CONNECTION_CODE = 1646037375;
    public const DEFAULT_IDENTITY_FIELD = '__identity';
    public const FIELD_TABLE = 'table';
    public const FIELD_FIELD = 'field';
    public const FIELD_SKIP = 'skip';
    public const FIELD_IF_EMPTY = 'ifEmpty';
    public const FIELD_IF_NOT_EMPTY = 'ifNotEmpty';
    public const FIELD_UNSET_KEYS = 'unsetKeys';

    /**
     * Tells if the configuration is valid
     *
     * @param array $configuration
     * @return bool
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
            && (!is_array($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY])
                || empty($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY])
                || !is_string($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY][self::FIELD_FIELD])
                || empty($configuration[self::FIELD_SKIP][self::FIELD_IF_EMPTY][self::FIELD_FIELD])
            )
        ) {
            return false;
        }

        if (
            isset($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY])
            && (!is_array($configuration[self::FIELD_SKIP][self::FIELD_IF_NOT_EMPTY])
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
     * @param array|null $configuration
     * @return bool
     * @throws InvalidConfigurationException
     */
    public function persist($object, array $configuration = null)
    {
        if ($this->shouldSkip($object, $configuration)) {
            return false;
        }
        $tableName = $configuration[self::FIELD_TABLE];

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


        if (isset($object[self::DEFAULT_IDENTITY_FIELD])) {
            $data = $object;
            $uid = $object[self::DEFAULT_IDENTITY_FIELD];
            unset($data[self::DEFAULT_IDENTITY_FIELD]);
            try {

                $this->connection->update(
                    $tableName,
                    $data,
                    ['uid' => $uid]
                );
            } catch (PersistenceException $exception) {
                $message = 'Update Exception:' . PHP_EOL;
                $message .= 'Data:' . PHP_EOL;
                $message .= json_encode($object);

                throw new PersistenceException(
                    $message,
                    1647701464,
                    $exception
                );
            }

            return true;
        }


        try {
            $this->connection->insert(
                $tableName,
                $object
            );
        } catch (PersistenceException $exception) {
            $message = 'Insert Exception:' . PHP_EOL;
            $message .= 'Data:' . PHP_EOL;
            $message .= json_encode($object);

            throw new PersistenceException(
                $message,
                1647701464,
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
     * @param array|null $configuration
     * @return void
     */
    public function persistAll($result = null, array $configuration = null)
    {
    }

    /**
     * Tells if the record should be skipped, i.e. not be persisted
     * @param array $record
     * @param array $configuration
     * @return bool
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
