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
 * Class DataSourceCSV
 */
class DataSourceCSV
    implements DataSourceInterface
{
    use IdentifiableTrait, ConfigurableTrait, ResourceTrait;

    protected static $characterProperties = ['delimiter', 'enclosure', 'escape'];

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!$this->pathValidator->validate($configuration)) {
            return false;
        }

        if (isset($configuration['fields'])) {
            if (!is_string($configuration['fields']) || empty($configuration['fields'])) {
                return false;
            }
        }

        foreach (self::$characterProperties as $property) {
            if (isset($configuration[$property])) {
                $value = $configuration[$property];
                if (
                    !is_string($value)
                    || empty($value)
                    || strlen($value) != 1
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Reads an CSV source and translates items into an array of records
     *
     * @param array $configuration Source query configuration
     * @return array Array of records or empty array
     */
    public function getRecords(array $configuration)
    {
        $records = [];

        $resource = rtrim($this->loadResource($configuration));

        if (!empty($resource)) {
            $delimiter = null;
            $enclosure = null;
            $escape = null;

            if (isset($configuration['delimiter'])) {
                $delimiter = $configuration['delimiter'];
            }
            if (isset($configuration['enclosure'])) {
                $enclosure = $configuration['enclosure'];
            }
            if (isset($configuration['escape'])) {
                $escape = $configuration['escape'];
            }

            $rows = array_filter(str_getcsv($resource, "\n"));

            $records = array_map(function ($d) use ($delimiter, $enclosure, $escape) {
                return str_getcsv($d, $delimiter, $enclosure, $escape);
            }, $rows);

            $headers = $records[0];
            if (isset($configuration['fields'])) {
                $headers = GeneralUtility::trimExplode(',', $configuration['fields'], true);
            } else {
                array_shift($records); // remove column header
            }

            array_walk($records, function (&$a) use ($records, $headers) {
                $a = array_combine($headers, $a);
            });

        }

        return $records;
    }
}
