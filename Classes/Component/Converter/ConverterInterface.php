<?php
namespace CPSIT\T3import\Component\Converter;

/**
 * Interface ConverterInterface
 *
 * @package CPSIT\T3import\Component\Converter
 */
interface ConverterInterface {
	/**
	 * @param array $record
	 * @param array $configuration
	 * @return mixed
	 */
	public function convert(array $record, array $configuration);

	/**
	 * @param array $configuration
	 * @return mixed
	 */
	public function isDisabled($configuration);

	/**
	 * @param array $configuration
	 * @return mixed
	 */
	public function isConfigurationValid(array $configuration);

	/**
	 * Sets the configuration
	 *
	 * @param array $configuration
	 * @return mixed
	 */
	public function setConfiguration($configuration);

	/**
	 * Returns the configuration
	 *
	 * @return array | null
	 */
	public function getConfiguration();
}