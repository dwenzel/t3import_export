<?php
namespace CPSIT\T3import;

use CPSIT\T3import\InvalidConfigurationException;

trait ConfigurableTrait {
	/**
	 * Configuration for this component
	 * A plain TypoScript array
	 *
	 * @var array
	 */
	protected $configuration;


	/**
	 * Tells if a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	abstract public function isConfigurationValid(array $configuration);

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Sets the configuration if it is valid.
	 * Throws an exception otherwise.
	 *
	 * @param array $configuration
	 * @throws InvalidConfigurationException
	 */
	public function setConfiguration(array $configuration) {
		if ($this->isConfigurationValid($configuration)) {
			$this->configuration = $configuration;
		} else {
			throw new InvalidConfigurationException(
				'Configuration for ' . __CLASS__
				. ' is not valid.',
				1451659793
			);
		}
	}
}