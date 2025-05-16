<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\Component\ComponentInterface;

/**
 * Interface DataSourceInterface
 *
 * Describes data sources.
 */
interface DataSourceInterface extends ComponentInterface
{
    /**
     * Fetches records from a data source.
     *
     * @param array $configuration Source query configuration
     * @return array Array of records or empty array
     */
    public function getRecords(array $configuration);
}
