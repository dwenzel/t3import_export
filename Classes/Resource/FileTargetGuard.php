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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Checks the files which components write themselves (not through FAL).
 *
 * Data files (e.g. of `DataTargetCSV`) are written only
 * - with an allowed file extension
 *   (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetExtensions']`,
 *   default: csv, tsv, txt, xml, json, yaml, yml) and
 * - inside an allowed directory
 *   (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetDirectories']`,
 *   default: `fileadmin/` and `typo3temp/` of the public directory and the
 *   var directory). Relative directories are relative to the public directory.
 *
 * File names matching `$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`
 * or the default pattern of TYPO3, server configuration files (e.g.
 * `.htaccess`, `.user.ini`), names with a colon, `?` or `#` and names ending in
 * a dot or space are never written. Paths with `..` segments and stream wrappers (`scheme://`) are
 * rejected. Symbolic links are resolved: the real path must be inside the real
 * path of an allowed directory, so an allowed directory may itself be a link.
 */
final readonly class FileTargetGuard
{
    public const ERROR_PATH_NOT_ALLOWED = 1795400101;
    public const ERROR_DIRECTORY_NOT_ALLOWED = 1795400102;
    public const ERROR_FILE_TYPE_NOT_ALLOWED = 1795400103;

    public const DEFAULT_EXTENSIONS = ['csv', 'tsv', 'txt', 'xml', 'json', 'yaml', 'yml'];

    /**
     * Server configuration files, which the default fileDenyPattern of
     * TYPO3 does not cover completely
     */
    private const SERVER_CONFIGURATION_FILES = '/^(?:\.ht[a-z0-9_.-]*|\.user\.ini|php\.ini|web\.config)$/i';

    /**
     * @var list<string>
     */
    private array $extensions;

    /**
     * @var list<string> absolute paths without trailing slash
     */
    private array $directories;

    private FileNameValidator $defaultFileNameValidator;

    /**
     * @param list<string>|null $extensions allowed file extensions of data files; default: the configured ones
     * @param list<string>|null $directories allowed directories, absolute or relative to the public directory; default: the configured ones
     */
    public function __construct(
        ?array $extensions = null,
        ?array $directories = null,
        private FileNameValidator $fileNameValidator = new FileNameValidator(),
    ) {
        $this->defaultFileNameValidator = new FileNameValidator(FileNameValidator::DEFAULT_FILE_DENY_PATTERN);
        $this->extensions = array_map(
            static fn(string $extension): string => strtolower(ltrim($extension, '.')),
            $extensions ?? self::configuredList('fileTargetExtensions', self::DEFAULT_EXTENSIONS)
        );
        $this->directories = array_map(
            static fn(string $directory): string => rtrim(
                PathUtility::isAbsolutePath($directory) ? $directory : Environment::getPublicPath() . '/' . $directory,
                '/'
            ),
            $directories ?? self::configuredList(
                'fileTargetDirectories',
                [
                    Environment::getPublicPath() . '/fileadmin',
                    Environment::getPublicPath() . '/typo3temp',
                    Environment::getVarPath(),
                ]
            )
        );
    }

    /**
     * Resolves the path of a data file which is to be written.
     *
     * @return string absolute path
     * @throws InvalidArgumentException if the path is not allowed
     */
    public function resolveDataFile(string $path): string
    {
        if (
            trim($path) === ''
            || str_contains($path, "\0")
            || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path) === 1
            || \in_array('..', preg_split('#[/\\\\]+#', $path) ?: [], true)
            || PathUtility::isExtensionPath($path)
        ) {
            throw $this->notAllowed($path, 'empty paths, EXT: paths, ".." segments and stream wrappers are not allowed.', self::ERROR_PATH_NOT_ALLOWED);
        }
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($absolutePath === '') {
            throw $this->notAllowed($path, 'it is outside of the project.', self::ERROR_PATH_NOT_ALLOWED);
        }
        $realPath = self::realPath($absolutePath);
        if ($realPath === null || !$this->isInDirectories($realPath)) {
            throw $this->notAllowed(
                $path,
                'files can only be written to the directories ' . implode(', ', $this->directories) . '.',
                self::ERROR_DIRECTORY_NOT_ALLOWED
            );
        }
        $this->assertDataFileName(basename($absolutePath));
        // the target of a symbolic link
        $this->assertDataFileName(basename($realPath));

        return $absolutePath;
    }

    /**
     * Checks the name of a data file: allowed file extension and file name.
     *
     * @throws InvalidArgumentException if the file name is not allowed
     */
    public function assertDataFileName(string $fileName): void
    {
        $this->assertFileName($fileName);
        if (!\in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $this->extensions, true)) {
            throw $this->notAllowed(
                $fileName,
                'only files of the types ' . implode(', ', $this->extensions) . ' can be written.',
                self::ERROR_FILE_TYPE_NOT_ALLOWED
            );
        }
    }

    /**
     * Checks a file name against the configured fileDenyPattern of TYPO3
     * and against its default pattern (so that an empty or weaker
     * configured pattern does not disable the check), and denies server
     * configuration files, colons (alternate data streams), `?` and `#` and names
     * ending in a dot or space. For files of any type, e.g. copied images.
     *
     * @throws InvalidArgumentException if the file name is not allowed
     */
    public function assertFileName(string $fileName): void
    {
        if (
            $fileName === ''
            || str_contains($fileName, ':')
            || strpbrk($fileName, '?#') !== false
            || preg_match('/[. ]$/', $fileName) === 1
            || !$this->fileNameValidator->isValid($fileName)
            || !$this->defaultFileNameValidator->isValid($fileName)
            || preg_match(self::SERVER_CONFIGURATION_FILES, $fileName) === 1
        ) {
            throw $this->notAllowed($fileName, 'the file type is not allowed (fileDenyPattern).', self::ERROR_FILE_TYPE_NOT_ALLOWED);
        }
    }

    /**
     * @param list<string> $default
     * @return list<string>
     */
    private static function configuredList(string $key, array $default): array
    {
        $configured = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export'][$key] ?? null;
        if (\is_string($configured)) {
            $configured = GeneralUtility::trimExplode(',', $configured, true);
        }
        if (!\is_array($configured)) {
            return $default;
        }

        return array_values(array_filter(
            $configured,
            static fn(mixed $value): bool => \is_string($value) && trim($value) !== ''
        ));
    }

    private function isInDirectories(string $realPath): bool
    {
        foreach ($this->directories as $directory) {
            $realDirectory = self::realPath($directory);
            if ($realDirectory !== null && str_starts_with($realPath, rtrim($realDirectory, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolves symbolic links in `$absolutePath` (without `..` segments): the
     * real path of the nearest existing part of the path, followed by the
     * parts that do not exist yet.
     *
     * @return string|null null if a symbolic link cannot be resolved
     */
    private static function realPath(string $absolutePath): ?string
    {
        $existing = $absolutePath;
        $missing = '';
        while (!file_exists($existing) && !is_link($existing)) {
            $parent = \dirname($existing);
            if ($parent === $existing) {
                return $absolutePath;
            }
            $missing = '/' . basename($existing) . $missing;
            $existing = $parent;
        }
        $realPath = realpath($existing);
        if ($realPath === false) {
            return null;
        }

        return rtrim($realPath, '/') . $missing;
    }

    private function notAllowed(string $path, string $reason, int $code): InvalidArgumentException
    {
        return new InvalidArgumentException(
            \sprintf('The file "%s" cannot be written: %s', str_replace("\0", '\0', $path), $reason),
            $code
        );
    }
}
