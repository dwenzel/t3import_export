<?php

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ResourceTrait
{

    /**
     * Wrapper method for testing purposes
     *
     * @param $path
     * @return string
     * @codeCoverageIgnore
     */
    protected function getAbsoluteFilePath($path)
    {
        return GeneralUtility::getFileAbsFileName($path);
    }

    /**
     * Loads a resource from file or URL
     *
     * @param array $configuration Must contain a file or url key as resource path
     * @return mixed|null Loaded resource
     */
    public function loadResource(array $configuration)
    {
        $resource = null;
        $resourcePath = '';
        if (isset($configuration['file'])) {
            $resourcePath = $configuration['file'];
        }

        if (isset($configuration['url'])) {
            $resourcePath = $configuration['url'];
        }

        $absoluteFilePath = $this->getAbsoluteFilePath($resourcePath);
        if (is_file($absoluteFilePath) === true) {
            $resource = file_get_contents($absoluteFilePath);
        } elseif (GeneralUtility::isValidUrl($resourcePath) === true) {
            $resource = GeneralUtility::getURL($resourcePath);
        }

        return $resource;
    }
}
