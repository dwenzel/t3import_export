<?php

declare(strict_types=1);

namespace CPSIT\T3importExport;

/**
 * Interface ConfigurableInterface
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
     * @throws InvalidConfigurationException
     */
    public function setConfiguration(array $configuration);
}
