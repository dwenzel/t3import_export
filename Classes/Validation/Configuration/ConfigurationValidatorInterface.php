<?php
namespace CPSIT\T3importExport\Validation\Configuration;

/**
 * Interface ConfigurationValidatorInterface
 *
 * @package CPSIT\T3importExport\Validation\Configuration
 */
/**
 * Interface ConfigurationValidatorInterface
 *
 * @package CPSIT\T3importExport\Validation\Configuration
 */
interface ConfigurationValidatorInterface
{
    /**
     * @param array $config
     * @return bool
     */
    public function validate(array $config);
}
