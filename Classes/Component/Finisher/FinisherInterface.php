<?php
namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface FinisherInterface
 *
 * @package CPSIT\T3importExport\Component\Finisher
 */
interface FinisherInterface
{
    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @param array|\Iterator|null $result Array with result records
     * @return bool
     */
    public function process($configuration, &$records, &$result);

    /**
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration);

    /**
     * Tells if the component is disabled
     * @param array $configuration
     * @param array $record
     * @param TaskResult|\Iterator|array $result
     * @return mixed
     */
    public function isDisabled($configuration, $record = null, TaskResult $result = null);

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
