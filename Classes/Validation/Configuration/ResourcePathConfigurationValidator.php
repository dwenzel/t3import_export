<?php

namespace CPSIT\T3importExport\Validation\Configuration;

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

/**
 * Class ResourcePathConfigurationValidator
 */
class ResourcePathConfigurationValidator implements ConfigurationValidatorInterface
{
    /**
     * Tells if the configuration is valid
     *
     * @param array $config
     * @return bool True for a valid configuration
     */
    public function validate(array $config)
    {
        if (empty($config)) {
            return false;
        }

        if (isset($config['file']) && isset($config['url'])) {
            return false;
        }

        if (isset($config['url']) && !is_string($config['url'])) {
            return false;
        }

        if (isset($config['file']) && !is_string($config['file'])) {
            return false;
        }

        if (isset($config['file']) && empty($this->getAbsoluteFilePath($config['file']))) {
            return false;
        }

        if (isset($config['url']) && !GeneralUtility::isValidUrl($config['url'])) {
            return false;
        }

        return true;
    }

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
}
