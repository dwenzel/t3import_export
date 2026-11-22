<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Dbal;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\ImportExportCore\Dbal\ConnectionProviderInterface;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Provides the connections of TYPO3 to the DBAL components of the core
 * (`DataSourceDBAL`, `DataTargetDBAL`, `RelationListPreProcessor`).
 *
 * Connection names are the names in
 * `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`. The default name of the
 * core, `default`, stands for the TYPO3 connection `Default`; both names can
 * be used in a configuration. A TYPO3 connection named `default` (lower case)
 * cannot be reached, since the name always refers to `Default`. Entries with
 * an empty configuration count as not configured.
 *
 * The core components get a connection by name only. The table mapping of
 * TYPO3 (`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']`) is applied by
 * `getConnectionNameForTable()` and `getConnectionForTable()`; for a table
 * mapped to another connection, set `connection` in the configuration of a
 * core component to that name.
 */
final readonly class Typo3ConnectionProvider implements ConnectionProviderInterface
{
    public function __construct(private ConnectionPool $connectionPool) {}

    public function hasConnection(string $name): bool
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::toTypo3Name($name)] ?? null;

        return \is_array($configuration) && $configuration !== [];
    }

    public function getConnection(string $name): Connection
    {
        if (!$this->hasConnection($name)) {
            throw new MissingResourceException(
                \sprintf('There is no database connection "%s" in $GLOBALS[\'TYPO3_CONF_VARS\'][\'DB\'][\'Connections\'].', $name),
                1795351767
            );
        }

        return $this->connectionPool->getConnectionByName(self::toTypo3Name($name));
    }

    /**
     * Returns the name of the connection that the table is mapped to, as the
     * core names it: `default` for the TYPO3 connection `Default` and for
     * tables without mapping.
     */
    public function getConnectionNameForTable(string $table): string
    {
        $name = $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$table] ?? '';
        if (!\is_string($name) || $name === '' || $name === ConnectionPool::DEFAULT_CONNECTION_NAME) {
            return self::DEFAULT_CONNECTION;
        }

        return $name;
    }

    /**
     * Returns the connection of the table according to the table mapping.
     */
    public function getConnectionForTable(string $table): Connection
    {
        return $this->connectionPool->getConnectionForTable($table);
    }

    private static function toTypo3Name(string $name): string
    {
        return $name === self::DEFAULT_CONNECTION ? ConnectionPool::DEFAULT_CONNECTION_NAME : $name;
    }
}
