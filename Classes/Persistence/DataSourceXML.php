<?php

namespace CPSIT\T3importExport\Persistence;

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

use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\Resource\ResourceTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataSourceXML
 */
class DataSourceXML implements DataSourceInterface
{
    use IdentifiableTrait, ConfigurableTrait, ResourceTrait;

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (empty($configuration)) {
            return false;
        }

        if (isset($configuration['file']) && isset($configuration['url'])) {
            return false;
        }

        if (isset($configuration['url']) && !is_string($configuration['url'])) {
            return false;
        }

        if (isset($configuration['file']) && !is_string($configuration['file'])) {
            return false;
        }

        if (isset($configuration['file']) && empty($this->getAbsoluteFilePath($configuration['file']))) {
            return false;
        }

        if (isset($configuration['url']) && !GeneralUtility::isValidUrl($configuration['url'])) {
            return false;
        }

        if (isset($configuration['expression']) && !is_string($configuration['expression'])) {
            return false;
        }

        return true;
    }

    /**
     * Reads an XML source and translates items into an array of records
     *
     * @param array $configuration Source query configuration
     * @return array Array of records or empty array
     */
    public function getRecords(array $configuration)
    {
        $records = [];

        $resource = $this->loadResource($configuration);

        if (!empty($resource)) {
            $xml = new \SimpleXMLElement($resource, LIBXML_NOCDATA);

            if (isset($configuration['expression'])) {
                $queryResult = $xml->xpath($configuration['expression']);
                if (is_array($queryResult) && count($queryResult) > 0) {
                    foreach ($queryResult as $key => $value) {
                        // convert to real PHP array; idea from soloman at http://www.php.net/manual/en/book.simplexml.php
                        $json = json_encode($value);
                        $records[$key] = json_decode($json, true);
                    }
                }
            }
        }

        return $records;
    }
}
