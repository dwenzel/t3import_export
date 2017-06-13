<?php

namespace CPSIT\T3importExport;

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ResourceTrait
{
    /**
     * Wrapper method for testing purposes
     *
     * @param $path
     * @return string
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
    protected function loadResource(array $configuration)
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
            $resource = GeneralUtility::getURL($absoluteFilePath, 0, false);
        } elseif (GeneralUtility::isValidUrl($resourcePath) === true) {
            $resource = GeneralUtility::getURL($resourcePath, 0, false);
        }

        return $resource;
    }
}
