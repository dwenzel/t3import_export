<?php
namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;

/**
 * Interface DataSourceInterface
 *
 * Describes data sources.
 *
 * @package CPSIT\T3importExport\Persistence
 */
interface DataSourceInterface extends ConfigurableInterface {
	/**
	 * Fetches records from a data source.
	 *
	 * @param array $configuration Source query configuration
	 * @return array Array of records or empty array
	 */
	public function getRecords(array $configuration);

	/**
	 * fetches a record uid from a data source
	 *
	 * @param array $configuration
	 * @param int $batchSize
	 * @param int $currentOffset
	 * @param bool $eof
	 * @return array Array of indexes (xml:node index, csv:line number, DB: uid, ...)
	 */
	public function getRecordsIndexes(array $configuration, $batchSize = 0, $currentOffset = 0, &$eof = false);
}
