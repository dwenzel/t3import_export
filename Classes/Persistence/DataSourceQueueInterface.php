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
interface DataSourceQueueInterface extends ConfigurableInterface
{
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
