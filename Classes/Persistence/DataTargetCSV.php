<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

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

use CPSIT\T3importExport\Utility\DeprecationUtility;

/**
 * Writes records into a CSV file.
 *
 * Keeps the defaults of versions before 4.0: the file starts with a byte
 * order mark (`bom: true`), and the first row holds the field names only if
 * `fields` is set (`header`).
 *
 * @deprecated since 4.0, will be removed in 5.0. Use
 * `CPSIT\ImportExportCore\File\DataTargetCSV`. Its defaults are `bom: false`
 * and `header: true`.
 */
class DataTargetCSV extends \CPSIT\ImportExportCore\File\DataTargetCSV
{
    public function setConfiguration(array $configuration): void
    {
        parent::setConfiguration(self::withLegacyDefaults($configuration));
    }

    public function persist(array|object $object, ?array $configuration = null): bool
    {
        DeprecationUtility::trigger(
            self::class . ' is deprecated since 4.0. Use ' . parent::class . '.',
            DeprecationUtility::FILE_COMPONENTS_ISSUE_URL
        );

        return parent::persist($object, $configuration === null ? null : self::withLegacyDefaults($configuration));
    }

    public function persistAll(mixed $result = null, ?array $configuration = null): void
    {
        parent::persistAll($result, $configuration === null ? null : self::withLegacyDefaults($configuration));
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private static function withLegacyDefaults(array $configuration): array
    {
        return $configuration + [
            self::KEY_BOM => true,
            self::KEY_HEADER => isset($configuration['fields']),
        ];
    }
}
