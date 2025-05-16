<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Component\Initializer;

use CPSIT\T3importExport\Component\ComponentInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface InitializerInterface
 */
interface InitializerInterface extends ComponentInterface
{
    /**
     * @param array $records Array with prepared records
     */
    public function process(array $configuration, array &$records): bool;

    public function isConfigurationValid(array $configuration): bool;

    /**
     * Tells if the component is disabled
     * @param TaskResult|\Iterator|array $result
     * @return mixed
     */
    public function isDisabled(array $configuration, array $record = [], ?TaskResult $result = null): bool;

    /**
     * Sets the configuration
     *
     * @return mixed
     */
    public function setConfiguration(array $configuration);

    /**
     * Returns the configuration
     */
    public function getConfiguration(): array;
}
