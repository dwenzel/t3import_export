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
     * @param TaskResult|\Iterator|array $result
     * @return mixed
     */
    public function isDisabled($configuration, $record = null, TaskResult $result = null);

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
    public function setConfiguration(array $configuration);

    /**
     * Returns the configuration
     *
     * @return array | null
     */
    public function getConfiguration();
}
