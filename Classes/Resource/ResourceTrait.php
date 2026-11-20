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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads files and URLs with the file rules of the extension (see
 * `ResourceAccess`): files only from the allowed directories and with an
 * allowed file type, URLs only from the allowed hosts.
 */
trait ResourceTrait
{
    protected ?ResourceAccess $resourceAccess = null;

    public function setResourceAccess(ResourceAccess $resourceAccess): void
    {
        $this->resourceAccess = $resourceAccess;
    }

    protected function getResourceAccess(): ResourceAccess
    {
        return $this->resourceAccess ??= GeneralUtility::makeInstance(ResourceAccess::class);
    }

    /**
     * Absolute path of a file that may be read
     *
     * @throws InvalidArgumentException if the path is not allowed
     */
    protected function getAbsoluteFilePath($path)
    {
        return $this->getResourceAccess()->resolveFile((string)$path);
    }

    /**
     * Loads a resource from file or URL
     *
     * @param array $configuration Must contain a file or url key as resource path
     * @return string|null Loaded resource, null if the file does not exist
     * @throws InvalidArgumentException if the file is not allowed
     * @throws MissingResourceException if the URL is not allowed or cannot be loaded
     */
    public function loadResource(array $configuration)
    {
        $location = $configuration['url'] ?? $configuration['file'] ?? null;
        if (!\is_string($location) || trim($location) === '') {
            return null;
        }

        return $this->getResourceAccess()->load($location);
    }
}
