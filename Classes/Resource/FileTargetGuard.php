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

use CPSIT\T3importExport\Exception\InvalidArgumentException;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;

/**
 * Checks the names of files which components write.
 *
 * File names matching `$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`
 * and server configuration files (e.g. `.htaccess`, `.user.ini`) are never
 * written.
 */
final class FileTargetGuard
{
    public const ERROR_FILE_TYPE_NOT_ALLOWED = 1795400103;

    /**
     * Server configuration files, which the default fileDenyPattern of
     * TYPO3 does not cover completely
     */
    private const SERVER_CONFIGURATION_FILES = '/^(?:\.ht[a-z0-9_.-]*|\.user\.ini|php\.ini|web\.config)$/i';

    private FileNameValidator $fileNameValidator;

    public function __construct(?FileNameValidator $fileNameValidator = null)
    {
        $this->fileNameValidator = $fileNameValidator ?? new FileNameValidator();
    }

    /**
     * Checks a file name against the fileDenyPattern of TYPO3 and denies
     * server configuration files.
     *
     * @throws InvalidArgumentException if the file name is not allowed
     */
    public function assertFileName(string $fileName): void
    {
        if (
            $fileName === ''
            || !$this->fileNameValidator->isValid($fileName)
            || preg_match(self::SERVER_CONFIGURATION_FILES, $fileName) === 1
        ) {
            throw new InvalidArgumentException(
                \sprintf('The file "%s" cannot be written: the file type is not allowed (fileDenyPattern).', str_replace("\0", '\0', $fileName)),
                self::ERROR_FILE_TYPE_NOT_ALLOWED
            );
        }
    }
}
