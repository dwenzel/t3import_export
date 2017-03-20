<?php
namespace CPSIT\T3importExport\Validation\Configuration;

/**
 * Interface ConfigurationValidatorInterface
 */
interface ConfigurationValidatorInterface {
	/**
	 * @param array $config
	 * @return bool
	 */
	public function validate(array $config);
}
