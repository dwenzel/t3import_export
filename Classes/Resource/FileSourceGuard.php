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
 * - or, for an `EXT:` path given as `sourcePath` in the configuration, below
 *   the directory of that `sourcePath` inside the extension. A value of a
 *   record cannot name an `EXT:` path.
 *
 * Paths with control characters, `..` segments and stream wrappers
 * (`scheme://`, `php:`, `data:`, `phar:` ...) are rejected. Symbolic links are
 * resolved: the real path must be inside the real path of an allowed
 * directory, so an allowed directory may itself be a link.
 *
 * URLs are loaded only with http and https and from an allowed host
 * (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`,
 * default: none). `example.org` allows this host with the default port of
 * the scheme (80, 443), `example.org:8080` this host with port 8080,
 * `*.example.org` the subdomains (optionally with a port) and `*` all hosts
 * and ports.
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
     * @var list<array{host: string, port: int|null}>|null lower case host names and ports (null: default port), null: all hosts
     */
    private ?array $allowedHosts;

    /**
     * @param list<string>|null $directories allowed directories, absolute or relative to the public directory; default: the configured ones
     * @param list<string>|null $allowedHosts allowed hosts (`host` or `host:port`), `*.example.org` for subdomains, `*` for all hosts; default: the configured ones
     */
    public function __construct(
        ?array $directories = null,
        ?array $allowedHosts = null,
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
            static fn(string $host): string => strtolower(trim($host)),
            $allowedHosts ?? self::configuredList('allowedHosts', [])
        );
        $this->allowedHosts = \in_array('*', $hosts, true) ? null : array_map(
            static function (string $host): array {
                if (preg_match('/^(.+):(\d+)$/', $host, $matches) === 1 && !str_ends_with($matches[1], ':')) {
                    return ['host' => rtrim($matches[1], '.'), 'port' => (int)$matches[2]];
                }

                return ['host' => rtrim($host, '.'), 'port' => null];
            },
            $hosts
        );
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
     * An `EXT:` path is only allowed if `$sourcePath` (the `sourcePath` of the
     * configuration) is an `EXT:` path and `$path` starts with it. The file
     * must be below the directory of `$sourcePath`, which must be inside the
     * extension.
     *
     * @return string real path of the file (symbolic links resolved)
     * @throws InvalidArgumentException if the path is not allowed
     */
    public function resolveFile(string $path, ?string $sourcePath = null): string
    {
        $isExtensionPath = PathUtility::isExtensionPath($path);
        if (
            self::isInvalidPath($path)
            || ($isExtensionPath && ($sourcePath === null || !PathUtility::isExtensionPath($sourcePath) || !str_starts_with($path, $sourcePath)))
        ) {
            throw $this->notAllowed(
                $path,
                'empty paths, control characters, ".." segments, stream wrappers and EXT: paths which are not the configured source path are not allowed.',
                self::ERROR_PATH_NOT_ALLOWED
            );
        }
        if ($isExtensionPath) {
            return $this->resolveExtensionFile($path, (string)$sourcePath);
        }
        $absolutePath = PathUtility::isAbsolutePath($path) ? $path : Environment::getPublicPath() . '/' . $path;
        $realPath = self::realPath($absolutePath);
        if ($realPath === null || !self::isInDirectories($realPath, $this->directories)) {
            throw $this->notAllowed(
                $path,
                'files can only be read from the directories ' . implode(', ', $this->directories) . '.',
                self::ERROR_DIRECTORY_NOT_ALLOWED
            );
        }

        return $realPath;
    }

    /**
     * @return string real path of the file
     * @throws InvalidArgumentException if the file is not below the directory of the source path inside the extension
     */
    private function resolveExtensionFile(string $path, string $sourcePath): string
    {
        if (self::isInvalidPath($sourcePath)) {
            throw $this->notAllowed($sourcePath, 'the source path is not allowed.', self::ERROR_PATH_NOT_ALLOWED);
        }
        $extensionDirectory = self::extensionDirectory($sourcePath);
        $absoluteSourcePath = GeneralUtility::getFileAbsFileName($sourcePath);
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($extensionDirectory === null) {
            throw $this->notAllowed($path, 'the extension of the source path is not loaded.', self::ERROR_DIRECTORY_NOT_ALLOWED);
        }
        if ($absoluteSourcePath === '' || $absolutePath === '') {
            throw $this->notAllowed($path, 'the path cannot be resolved inside the extension.', self::ERROR_DIRECTORY_NOT_ALLOWED);
        }
        // the directory of the source path: the path itself if it ends with a slash, else its parent
        $sourceDirectory = str_ends_with($sourcePath, '/') ? rtrim($absoluteSourcePath, '/') : \dirname($absoluteSourcePath);
        $realSourceDirectory = self::realPath($sourceDirectory);
        $realPath = self::realPath($absolutePath);
        if ($realSourceDirectory === null || !self::isInDirectories($realSourceDirectory . '/', [$extensionDirectory])) {
            throw $this->notAllowed(
                $path,
                'the source directory must be inside the extension (a source path without trailing slash uses its parent directory).',
                self::ERROR_DIRECTORY_NOT_ALLOWED
            );
        }
        if ($realPath === null || !self::isInDirectories($realPath, [$realSourceDirectory])) {
            throw $this->notAllowed(
                $path,
                'files of EXT: paths must be below the directory of the source path inside the extension.',
                self::ERROR_DIRECTORY_NOT_ALLOWED
            );
        }

        return $realPath;
    }

    private static function isInvalidPath(string $path): bool
    {
        return trim($path) === ''
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
            // stream wrappers and schemes like php:, data:, phar: (a single letter is a drive letter)
            || (!PathUtility::isExtensionPath($path) && preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]+:#', $path) === 1)
            || \in_array('..', preg_split('#[/\\\\]+#', $path) ?: [], true);
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

    /**
     * Whether the host and port of a URL are allowed. An entry without port
     * allows only the default port of the scheme (80 for http, 443 for https).
     */
    public function isHostAllowed(string $url): bool
    {
        $host = rtrim(strtolower((string)parse_url($url, PHP_URL_HOST)), '.');
        if ($host === '') {
            return false;
        }
        if ($this->allowedHosts === null) {
            return true;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port = parse_url($url, PHP_URL_PORT) ?? $defaultPort;
        foreach ($this->allowedHosts as $allowedHost) {
            $hostMatches = $host === $allowedHost['host']
                || (str_starts_with($allowedHost['host'], '*.') && str_ends_with($host, substr($allowedHost['host'], 1)));
            if ($hostMatches && $port === ($allowedHost['port'] ?? $defaultPort)) {
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
     * @param string $realPath real path (see `realPath()`)
     * @param list<string> $directories
     */
    private static function isInDirectories(string $realPath, array $directories): bool
    {
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
