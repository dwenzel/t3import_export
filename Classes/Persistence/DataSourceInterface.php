<?php
namespace CPSIT\T3import\Persistence;

/**
 * Interface DataSourceInterface
 *
 * Describes data sources.
 *
 * @package CPSIT\T3import\Persistence
 */
interface DataSourceInterface {
	/**
	 * Fetches records from a data source.
	 *
	 * @param array $configuration Source query configuration
	 * @return array Array of records or empty array
	 */
	public function getRecords(array $configuration);
}