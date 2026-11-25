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
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Applies the file rules of the extension to the components that read or
 * write files themselves (not through the resource loaders of the core):
 * files through `Typo3PathResolver`, URLs through the allowed hosts of
 * `ResourceLoaderFactory`.
 */
class ResourceAccess
{
    public function __construct(
        private ?Typo3PathResolver $pathResolver = null,
        private ?HttpResourceLoader $httpResourceLoader = null,
    ) {}

    public static function isUrl(string $location): bool
    {
        return preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $location) === 1;
    }

    /**
     * Absolute path of a file that may be read.
     *
     * @throws InvalidArgumentException if the path is not allowed
     */
    public function resolveFile(string $path): string
    {
        return $this->getPathResolver()->resolve($path);
    }

    public function isFileAllowed(string $path): bool
    {
        try {
            $this->resolveFile($path);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Applies the file type rules of TYPO3 to a file outside of the allowed
     * directories, e.g. in a FAL storage.
     *
     * @throws InvalidArgumentException
     */
    public function assertFileTypeAllowed(string $path): void
    {
        $this->getPathResolver()->assertFileTypeAllowed($path);
    }

    public function isUrlAllowed(string $url): bool
    {
        $loader = $this->getHttpResourceLoader();

        return $loader->supports($url) && $loader->isHostAllowed($url);
    }

    /**
     * @throws MissingResourceException if the URL is not an http(s) URL or its host is not allowed
     */
    public function assertUrlAllowed(string $url): void
    {
        if (!$this->isUrlAllowed($url)) {
            throw new MissingResourceException(
                \sprintf('Resource "%s" cannot be loaded: the host is not allowed.', $url),
                HttpResourceLoader::ERROR_HOST_NOT_ALLOWED
            );
        }
    }

    /**
     * Opens a URL with the HTTP resource loader of the core. The loader
     * follows redirects itself and checks each target like the URL.
     *
     * @throws MissingResourceException if the URL is not allowed or cannot be loaded
     */
    public function openUrl(string $url): StreamInterface
    {
        return $this->getHttpResourceLoader()->open($url);
    }

    /**
     * Contents of a file or URL; null if the file does not exist.
     *
     * @throws InvalidArgumentException if the file is not allowed
     * @throws MissingResourceException if the URL is not allowed or cannot be loaded
     */
    public function load(string $location): ?string
    {
        if (self::isUrl($location)) {
            return $this->openUrl($location)->getContents();
        }
        $path = $this->resolveFile($location);
        if (!is_file($path)) {
            return null;
        }
        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    private function getPathResolver(): Typo3PathResolver
    {
        return $this->pathResolver ??= new Typo3PathResolver();
    }

    private function getHttpResourceLoader(): HttpResourceLoader
    {
        return $this->httpResourceLoader ??= GeneralUtility::makeInstance(ResourceLoaderFactory::class)
            ->createHttpResourceLoader();
    }
}
