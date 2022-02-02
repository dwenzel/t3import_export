<?php
namespace CPSIT\T3importExport;

use CPSIT\T3importExport\InvalidConfigurationException;

/**
 * Interface ConfigurableInterface
 *
 * @package CPSIT\T3importExport
 */
interface ConfigurableInterface
{

    public const KEY_TABLE = 'table';
    public const KEY_CONFIG = 'config';
    public const KEY_WHERE = 'where';
    public const KEY_DISABLED = 'disabled';
    public const KEY_SET_FIELDS = 'setFields';
    public const KEY_TYPES = 'types';

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool;

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
