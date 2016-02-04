<?php
namespace CPSIT\T3import\Component\Initializer;

/**
 * Interface InitializerInterface
 *
 * @package CPSIT\T3import\Component\Initializer
 */
interface InitializerInterface {
	/**
	 * @param array $configuration
	 * @param array $records Array with prepared records
	 * @return bool
	 */
	public function process($configuration, &$records);

	/**
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration);

	/**
	 * Tells if the component is disabled
	 *
	 * @param array $configuration
	 * @param array $records Array with prepared records
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