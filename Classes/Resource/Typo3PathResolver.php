<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Resource;

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

use CPSIT\ImportExportCore\Resource\PathResolverInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolves the paths of the file sources and targets of the core like TYPO3
 * resolves file names (`GeneralUtility::getFileAbsFileName()`):
 *
 * - `EXT:extension_key/path`: path in an extension
 * - relative paths: relative to the public directory, e.g. `fileadmin/import.csv`
 * - absolute paths: only inside the project directory or the paths allowed
 *   with `$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']`
 *
 * Paths with `..` segments and stream wrappers (`scheme://`) are rejected.
 */
final class Typo3PathResolver implements PathResolverInterface
{
    public const int ERROR_NOT_ALLOWED = 1795300101;

    public function resolve(string $path): string
    {
        if (
            $path === ''
            || str_contains($path, "\0")
            || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path) === 1
        ) {
            throw $this->notAllowed($path);
        }

        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($absolutePath === '') {
            throw $this->notAllowed($path);
        }

        return $absolutePath;
    }

    private function notAllowed(string $path): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            \sprintf('The path "%s" is not allowed.', str_replace("\0", '\0', $path)),
            self::ERROR_NOT_ALLOWED
        );
    }
}
