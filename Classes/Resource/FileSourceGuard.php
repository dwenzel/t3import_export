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
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Checks the source files which components copy themselves (not through FAL),
 * e.g. `GenerateFileResource` and `GenerateUploadFile`.
 *
 * Local files are read only
 * - inside an allowed directory
 *   (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories']`,
 *   default: `fileadmin/` of the public directory and `import/` of the var
 *   directory). Relative directories are relative to the public directory.
 * - or, for `EXT:` paths, inside the directory of the extension.
 *
 * Paths with control characters, `..` segments and stream wrappers
 * (`scheme://`, `php:`, `data:`, `phar:` ...) are rejected. Symbolic links are
 * resolved: the real path must be inside the real path of an allowed
 * directory, so an allowed directory may itself be a link.
 *
 * URLs are loaded only with http and https and from an allowed host
 * (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`,
 * default: none). `example.org` allows this host, `*.example.org` its
 * subdomains and `*` all hosts.
 */
final readonly class FileSourceGuard
{
    public const ERROR_PATH_NOT_ALLOWED = 1795400201;
    public const ERROR_DIRECTORY_NOT_ALLOWED = 1795400202;
    public const ERROR_URL_NOT_ALLOWED = 1795400203;
    public const ERROR_HOST_NOT_ALLOWED = 1795400204;

    /**
     * @var list<string> absolute paths without trailing slash
     */
    private array $directories;

    /**
     * @var list<string>|null lower case host names, null: all hosts
     */
    private ?array $allowedHosts;

    /**
     * @param list<string>|null $directories allowed directories, absolute or relative to the public directory; default: the configured ones
     * @param list<string>|null $allowedHosts allowed hosts, `*.example.org` for subdomains, `*` for all hosts; default: the configured ones
     */
    public function __construct(
        ?array $directories = null,
        ?array $allowedHosts = null,
        private bool $allowExtensionPaths = true,
    ) {
        $this->directories = array_map(
            static fn(string $directory): string => rtrim(
                PathUtility::isAbsolutePath($directory) ? $directory : Environment::getPublicPath() . '/' . $directory,
                '/'
            ),
            $directories ?? self::configuredList(
                'fileSourceDirectories',
                [
                    Environment::getPublicPath() . '/fileadmin',
                    Environment::getVarPath() . '/import',
                ]
            )
        );
        $hosts = array_map(
            static fn(string $host): string => rtrim(strtolower(trim($host)), '.'),
            $allowedHosts ?? self::configuredList('allowedHosts', [])
        );
        $this->allowedHosts = \in_array('*', $hosts, true) ? null : $hosts;
    }

    /**
     * Whether a location is a URL (`scheme://...`). Locations with a scheme
     * other than http and https are rejected by `assertUrlAllowed()`.
     */
    public static function isUrl(string $location): bool
    {
        return preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $location) === 1;
    }

    /**
     * Joins the source path of the configuration and a value of the record.
     * If the source path is a URL, the result must be a URL of the same
     * scheme, host, port and user information (the value cannot change the
     * host, e.g. with `@` or `.`) and of an allowed host.
     *
     * @throws InvalidArgumentException if the source path is a URL and the result is not allowed
     */
    public function buildLocation(?string $sourcePath, string $value): string
    {
        if ($sourcePath === null || $sourcePath === '') {
            return $value;
        }
        $location = $sourcePath . $value;
        if (!self::isUrl($sourcePath)) {
            return $location;
        }
        $this->assertUrlAllowed($location);
        $base = parse_url($sourcePath);
        $result = parse_url($location);
        foreach (['scheme', 'host', 'port', 'user', 'pass'] as $component) {
            if (
                !\is_array($base) || !\is_array($result)
                || strtolower((string)($base[$component] ?? '')) !== strtolower((string)($result[$component] ?? ''))
            ) {
                throw $this->notAllowed($location, 'the value must not change the scheme, host or port of the source path.', self::ERROR_URL_NOT_ALLOWED);
            }
        }

        return $location;
    }

    /**
     * Resolves the path of a local source file.
     *
     * @return string absolute path
     * @throws InvalidArgumentException if the path is not allowed
     */
    public function resolveFile(string $path): string
    {
        $isExtensionPath = PathUtility::isExtensionPath($path);
        if (
            trim($path) === ''
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
            // stream wrappers and schemes like php:, data:, phar: (a single letter is a drive letter)
            || (!$isExtensionPath && preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]+:#', $path) === 1)
            || \in_array('..', preg_split('#[/\\\\]+#', $path) ?: [], true)
            || ($isExtensionPath && !$this->allowExtensionPaths)
        ) {
            throw $this->notAllowed(
                $path,
                'empty paths, control characters, ".." segments and stream wrappers are not allowed.',
                self::ERROR_PATH_NOT_ALLOWED
            );
        }
        if ($isExtensionPath) {
            $absolutePath = GeneralUtility::getFileAbsFileName($path);
            $extensionDirectory = self::extensionDirectory($path);
            if ($absolutePath === '' || $extensionDirectory === null || !self::isInDirectories($absolutePath, [$extensionDirectory])) {
                throw $this->notAllowed($path, 'files of EXT: paths must be in the directory of the extension.', self::ERROR_DIRECTORY_NOT_ALLOWED);
            }

            return $absolutePath;
        }
        $absolutePath = PathUtility::isAbsolutePath($path) ? $path : Environment::getPublicPath() . '/' . $path;
        if (!self::isInDirectories($absolutePath, $this->directories)) {
            throw $this->notAllowed(
                $path,
                'files can only be read from the directories ' . implode(', ', $this->directories) . '.',
                self::ERROR_DIRECTORY_NOT_ALLOWED
            );
        }

        return $absolutePath;
    }

    /**
     * @throws InvalidArgumentException if the URL is not an http(s) URL or its host is not allowed
     */
    public function assertUrlAllowed(string $url): void
    {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if (
            preg_match('/[\x00-\x20\x7F\\\\]/', $url) === 1
            || ($scheme !== 'http' && $scheme !== 'https')
            || filter_var($url, FILTER_VALIDATE_URL) === false
        ) {
            throw $this->notAllowed($url, 'only http and https URLs can be loaded.', self::ERROR_URL_NOT_ALLOWED);
        }
        if (!$this->isHostAllowed($url)) {
            throw $this->notAllowed(
                $url,
                'the host is not allowed ($GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'t3import_export\'][\'allowedHosts\']).',
                self::ERROR_HOST_NOT_ALLOWED
            );
        }
    }

    public function isHostAllowed(string $url): bool
    {
        $host = rtrim(strtolower((string)parse_url($url, PHP_URL_HOST)), '.');
        if ($host === '') {
            return false;
        }
        if ($this->allowedHosts === null) {
            return true;
        }
        foreach ($this->allowedHosts as $allowedHost) {
            if (
                $host === $allowedHost
                || (str_starts_with($allowedHost, '*.') && str_ends_with($host, substr($allowedHost, 1)))
            ) {
                return true;
            }
        }

        return false;
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

    /**
     * Directory of the extension of an `EXT:` path; null if the extension is not loaded
     */
    private static function extensionDirectory(string $path): ?string
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        try {
            return $packageManager->getPackage($packageManager->extractPackageKeyFromPackagePath($path))->getPackagePath();
        } catch (UnknownPackageException|UnknownPackagePathException) {
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

    private function notAllowed(string $location, string $reason, int $code): InvalidArgumentException
    {
        return new InvalidArgumentException(
            \sprintf('The source "%s" cannot be read: %s', addcslashes($location, "\0..\37\177"), $reason),
            $code
        );
    }
}
