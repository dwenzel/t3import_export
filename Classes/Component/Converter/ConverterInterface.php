<?php
namespace CPSIT\T3importExport\Component\Converter;

use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface ConverterInterface
 *
 * @package CPSIT\T3importExport\Component\Converter
 */
interface ConverterInterface
{
    /**
     * @param array $record
     * @param array $configuration
     * @return mixed
     */
    public function convert(array $record, array $configuration);

    /**
     * Tells if the component is disabled
     *
     * @param array $configuration
     * @param array $record
     * @param TaskResult|null $result
     * @return bool
     */
    public function isDisabled(array $configuration, array $record = [], TaskResult $result = null): bool;

    /**
     * @param array $configuration
     * @return mixed
     */
    public function isConfigurationValid(array $configuration): bool;

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
     * @return array
     */
    public function getConfiguration(): array;
}
