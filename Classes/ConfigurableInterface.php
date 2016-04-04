<?php
namespace CPSIT\T3import;

use CPSIT\T3import\InvalidConfigurationException;

/**
 * Interface ConfigurableInterface
 *
 * @package CPSIT\T3import
 */
interface ConfigurableInterface {

	/**
	 * Tells if a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid(array $configuration);

	/**
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * Sets the configuration if it is valid.
	 * Throws an exception otherwise.
	 *
	 * @param array $configuration
	 * @throws InvalidConfigurationException
	 */
	public function setConfiguration(array $configuration);
}
