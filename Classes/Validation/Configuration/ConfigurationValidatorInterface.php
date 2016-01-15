<?php
namespace CPSIT\T3import\Validation\Configuration;

/**
 * Interface ConfigurationValidatorInterface
 *
 * @package CPSIT\T3import\Validation\Configuration
 */
/**
 * Interface ConfigurationValidatorInterface
 *
 * @package CPSIT\T3import\Validation\Configuration
 */
interface ConfigurationValidatorInterface {
	/**
	 * @param array $config
	 * @return bool
	 */
	public function validate(array $config);
}