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
 * Reads records from an XML file.
 *
 * A relative `expression` is evaluated relative to the root element, as in
 * versions before 4.0: `channel/item` selects the `item` elements of the
 * `channel` elements below the root element.
 *
 * @deprecated since 4.0, will be removed in 5.0. Use
 * `CPSIT\ImportExportCore\File\DataSourceXML`, which evaluates relative
 * expressions relative to the document.
 */
class DataSourceXML extends \CPSIT\ImportExportCore\File\DataSourceXML
{
    public function isConfigurationValid(array $configuration): bool
    {
        return parent::isConfigurationValid(self::withAbsoluteExpression($configuration));
    }

    public function getRecords(array $configuration): iterable
    {
        DeprecationUtility::trigger(
            self::class . ' is deprecated since 4.0. Use ' . parent::class . '.',
            DeprecationUtility::FILE_COMPONENTS_ISSUE_URL
        );

        return parent::getRecords(self::withAbsoluteExpression($configuration));
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private static function withAbsoluteExpression(array $configuration): array
    {
        $expression = $configuration[self::KEY_EXPRESSION] ?? null;
        if (\is_string($expression) && trim($expression) !== '' && !preg_match('#^\s*[/(]#', $expression)) {
            $configuration[self::KEY_EXPRESSION] = '/*/' . ltrim($expression);
        }

        return $configuration;
    }
}
