<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\Initializer;

use CPSIT\ImportExportCore\Component\Initializer\InitializerInterface as CoreInitializerInterface;
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
