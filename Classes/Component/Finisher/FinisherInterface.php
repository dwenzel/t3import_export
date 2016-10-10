<?php
namespace CPSIT\T3importExport\Component\Finisher;

/**
 * Interface FinisherInterface
 *
 * @package CPSIT\T3importExport\Component\Finisher
 */
interface FinisherInterface {
	/**
	 * @param array $configuration
	 * @param array $records Array with prepared records
	 * @param array|\Iterator|null $result Array with result records
	 * @return bool
	 */
	public function process($configuration, &$records, &$result);

	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration);

	/**
	 * Tells if the component is disabled
	 *
	 * @param array $configuration
	 * @param  array $records Array with prepared records
	 * @return bool
	 */
	public function isDisabled($configuration, $records = []);

	/**
	 * Sets the configuration
	 *
	 * @param array $configuration
	 * @return mixed
	 */
	public function setConfiguration(array $configuration);

	/**
	 * Returns the configuration
	 *
	 * @return array | null
	 */
	public function getConfiguration();
}
