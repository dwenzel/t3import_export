<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Configuration;

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

/**
 * Finds options for files in
 * `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']` which version
 * 4.0 does not read: the options `fileSourceDirectories`,
 * `fileTargetDirectories` and `fileTargetExtensions` of version 3.3, and
 * `allowedHosts` as string instead of a list. The commands show the
 * messages, `TransferService` logs them as warnings.
 */
final class FileOptionsCheck
{
    /**
     * Options of version 3.3 which are not read
     */
    public const array OPTIONS_OF_VERSION_3 = ['fileSourceDirectories', 'fileTargetDirectories', 'fileTargetExtensions'];

    /**
     * @return list<string>
     */
    public static function messages(): array
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export'] ?? null;
        if (!\is_array($configuration)) {
            return [];
        }
        $messages = [];
        foreach (self::OPTIONS_OF_VERSION_3 as $option) {
            if (!\array_key_exists($option, $configuration)) {
                continue;
            }
            $messages[] = \sprintf(
                'The option $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'t3import_export\'][\'%s\'] of version 3.3 is not read. %s See Documentation/DEPRECATIONS.md (Files).',
                $option,
                $option === 'fileTargetExtensions'
                    ? 'It has no equivalent: DataTargetCSV writes only files of the types csv, tsv and txt.'
                    : 'Use [\'fileDirectories\'] instead.'
            );
        }
        if (\is_string($configuration['allowedHosts'] ?? null)) {
            $messages[] = 'The option $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'t3import_export\'][\'allowedHosts\'] is a string and is not read, so no host is allowed. Use a list, e.g. [\'data.example.org\'], see Documentation/Persistence/Files.md (URLs).';
        }

        return $messages;
    }
}
