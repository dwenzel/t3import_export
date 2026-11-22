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

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Resource\PathResolverInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Resolves the paths of the file sources and targets of the core like TYPO3
 * resolves file names (`GeneralUtility::getFileAbsFileName()`), and applies
 * the file rules of TYPO3 and of the extension:
 *
 * - The file must be inside one of the allowed directories
 *   (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories']`,
 *   default: `fileadmin/` of the public directory and `import/` of the var
 *   directory). Relative paths are relative to the public directory.
 * - `EXT:` paths are allowed if `$allowExtensionPaths` is set, in the
 *   extension only for reading. The file must be inside the directory of the
 *   extension (or inside one of the allowed directories).
 * - The file name must not match `$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`.
 * - With the feature `security.system.enforceAllowedFileExtensions`, the
 *   extension must be in `textfile_ext`, `mediafile_ext` or `miscfile_ext`.
 *
 * Paths with `..` segments and stream wrappers (`scheme://`) are rejected.
 * Symbolic links are resolved before the check: the real path of the file (or,
 * for a file that does not exist yet, of its nearest existing parent
 * directory) must be inside the real path of an allowed directory or, for
 * `EXT:` paths, of the extension. An allowed directory or an extension may
 * itself be a symbolic link, e.g. a `fileadmin` that links to a shared
 * directory; a link inside it must not lead out of it.
 */
final readonly class Typo3PathResolver implements PathResolverInterface
{
    public const int ERROR_NOT_ALLOWED = 1795300101;
    public const int ERROR_DIRECTORY_NOT_ALLOWED = 1795300102;
    public const int ERROR_FILE_TYPE_NOT_ALLOWED = 1795300103;

    /**
     * @var list<string> absolute paths without trailing slash
     */
    private array $directories;

    /**
     * @param list<string>|null $directories allowed directories, absolute or
     *                                       relative to the public directory;
     *                                       default: the configured ones
     */
    public function __construct(
        private bool $allowExtensionPaths = true,
        ?array $directories = null,
        private Features $features = new Features(),
        private FileNameValidator $fileNameValidator = new FileNameValidator(),
    ) {
        $this->directories = array_map(
            static fn(string $directory): string => rtrim(
                PathUtility::isAbsolutePath($directory) ? $directory : Environment::getPublicPath() . '/' . $directory,
                '/'
            ),
            $directories ?? self::configuredDirectories()
        );
    }

    /**
     * @return list<string>
     */
    public static function configuredDirectories(): array
    {
        $configured = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories'] ?? null;
        if (!\is_array($configured)) {
            return [Environment::getPublicPath() . '/fileadmin', Environment::getVarPath() . '/import'];
        }

        return array_values(array_filter(
            $configured,
            static fn(mixed $directory): bool => \is_string($directory) && trim($directory) !== ''
        ));
    }

    /**
     * @return list<string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    public function resolve(string $path): string
    {
        if (
            trim($path) === ''
            || str_contains($path, "\0")
            || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path) === 1
            || \in_array('..', preg_split('#[/\\\\]+#', $path) ?: [], true)
        ) {
            throw $this->notAllowed($path, 'empty paths, null bytes, ".." segments and stream wrappers are not allowed.', self::ERROR_NOT_ALLOWED);
        }
        $isExtensionPath = PathUtility::isExtensionPath($path);
        if ($isExtensionPath && !$this->allowExtensionPaths) {
            throw $this->notAllowed($path, 'EXT: paths can only be read.', self::ERROR_NOT_ALLOWED);
        }

        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($absolutePath === '') {
            throw $this->notAllowed($path, 'it contains ".." or is outside of the project.', self::ERROR_NOT_ALLOWED);
        }
        if ($isExtensionPath) {
            $extensionDirectory = self::extensionDirectory($path);
            if (
                !self::isInDirectories($absolutePath, $extensionDirectory === null ? [] : [$extensionDirectory])
                && !self::isInDirectories($absolutePath, $this->directories)
            ) {
                throw $this->notAllowed(
                    $path,
                    'files must be in the directory of the extension or in one of the directories ' . implode(', ', $this->directories) . '.',
                    self::ERROR_DIRECTORY_NOT_ALLOWED
                );
            }
        } elseif (!self::isInDirectories($absolutePath, $this->directories)) {
            throw $this->notAllowed(
                $path,
                'files must be in one of the directories ' . implode(', ', $this->directories) . '.',
                self::ERROR_DIRECTORY_NOT_ALLOWED
            );
        }
        $this->assertFileTypeAllowed($absolutePath);

        return $absolutePath;
    }

    /**
     * Applies only the file type rules of TYPO3 (fileDenyPattern, allowed
     * file extensions) to the file name of `$path`, e.g. for files in a FAL
     * storage, whose directory is not in the allowed directories.
     *
     * @throws InvalidArgumentException
     */
    public function assertFileTypeAllowed(string $path): void
    {
        if (!$this->isFileTypeAllowed(basename($path))) {
            throw $this->notAllowed($path, 'the file type is not allowed in TYPO3 (fileDenyPattern, allowed file extensions).', self::ERROR_FILE_TYPE_NOT_ALLOWED);
        }
    }

    /**
     * Directory of the extension of an `EXT:` path; null if the extension is
     * not loaded
     */
    private static function extensionDirectory(string $path): ?string
    {
        $extensionKey = explode('/', substr($path, 4), 2)[0];
        try {
            return ExtensionManagementUtility::extPath($extensionKey);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param list<string> $directories
     */
    private static function isInDirectories(string $absolutePath, array $directories): bool
    {
        $realPath = self::realPath($absolutePath);
        if ($realPath === null) {
            return false;
        }
        foreach ($directories as $directory) {
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
     * @return string|null null if a symbolic link cannot be resolved, e.g.
     *                     it points to a file that does not exist
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

    private function isFileTypeAllowed(string $fileName): bool
    {
        if (!$this->fileNameValidator->isValid($fileName)) {
            return false;
        }
        if (!$this->features->isFeatureEnabled('security.system.enforceAllowedFileExtensions')) {
            return true;
        }
        $allowedExtensions = GeneralUtility::trimExplode(
            ',',
            strtolower(
                ($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] ?? '') . ','
                . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] ?? '') . ','
                . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext'] ?? '')
            ),
            true
        );

        return \in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $allowedExtensions, true);
    }

    private function notAllowed(string $path, string $reason, int $code): InvalidArgumentException
    {
        return new InvalidArgumentException(
            \sprintf('The path "%s" is not allowed: %s', str_replace("\0", '\0', $path), $reason),
            $code
        );
    }
}
