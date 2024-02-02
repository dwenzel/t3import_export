<?php

namespace CPSIT\T3importExport\Domain\Factory;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\Factory\FactoryFactory;
use CPSIT\T3importExport\Factory\FactoryInterface;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class TransferTaskFactory
 * builds import tasks from settings
 *
 * @package CPSIT\T3importExport\Domain\Factory
 */
class TransferTaskFactory extends AbstractFactory implements FactoryInterface
{
    final public const MISSING_SOURCE_EXCEPTION_CODE = 1_451_206_701;
    final public const MISSING_TARGET_EXCEPTION_CODE = 1_451_052_262;
    protected FactoryFactory $factoryFactory;

    public function __construct(
        ?FactoryFactory $factoryFactory = null
    )
    {
        $this->factoryFactory = $factoryFactory ?? GeneralUtility::makeInstance(FactoryFactory::class);
    }

    /**
     * Builds a task
     *
     * @param array $settings
     * @param string $identifier
     * @return TransferTask
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function get(array $settings = [], $identifier = null): TransferTask
    {
        /** @var TransferTask $task */
        $task = GeneralUtility::makeInstance(TransferTask::class);
        $this->assertValidSettings($settings, $identifier);

        $task->setIdentifier($identifier);
        $this->setTarget($task, $settings);
        $this->setSource($task, $settings);

        if (isset($settings['class'])
            && is_string($settings['class'])
        ) {
            $task->setTargetClass($settings['class']);
        }

        if (isset($settings['description'])
            && is_string($settings['description'])
        ) {
            $task->setDescription($settings['description']);
        }
        if (isset($settings['label'])) {
            $task->setLabel($settings['label']);
        }


        if (isset($settings['preProcessors'])
            && is_array($settings['preProcessors'])
        ) {
            $this->setPreProcessors($task, $settings['preProcessors'], $identifier);
        }
        if (isset($settings['postProcessors'])
            && is_array($settings['postProcessors'])
        ) {
            $this->setPostProcessors($task, $settings['postProcessors'], $identifier);
        }
        if (isset($settings['converters'])
            && is_array($settings['converters'])
        ) {
            $this->setConverters($task, $settings['converters'], $identifier);
        }
        if (isset($settings['finishers'])
            && is_array($settings['finishers'])
        ) {
            $this->setFinishers($task, $settings['finishers'], $identifier);
        }
        if (isset($settings['initializers'])
            && is_array($settings['initializers'])
        ) {
            $this->setInitializers($task, $settings['initializers'], $identifier);
        }

        return $task;
    }

    protected function assertValidSettings(array $settings, $identifier): void
    {
        if (!isset($settings['target'])
            || !is_array(($settings['target']))
        ) {
            throw new InvalidConfigurationException(
                'Invalid configuration for import task ' . $identifier .
                '. Target is missing or is not an array.',
                1_451_052_262
            );
        }

        if (!isset($settings['source'])
            || !is_array(($settings['source']))
        ) {
            throw new InvalidConfigurationException(
                'Invalid configuration for import task ' . $identifier .
                ' Source is missing or is not an array.',
                1_451_206_701
            );
        }
    }

    /**
     * Sets the target for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setTarget($task, array $settings): void
    {
        $targetIdentifier = null;
        if (isset($settings['target']['identifier'])
            && is_string($settings['target']['identifier'])
        ) {
            $targetIdentifier = $settings['target']['identifier'];
        }
        $dataTargetFactory = $this->factoryFactory->get(DataTargetInterface::class);
        $task->setTarget(
            $dataTargetFactory->get($settings['target'], $targetIdentifier)
        );
    }

    /**
     * Sets the source for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    protected function setSource($task, array $settings): void
    {
        $sourceIdentifier = null;
        if (isset($settings['source']['identifier'])
            && is_string($settings['source']['identifier'])
        ) {
            $sourceIdentifier = $settings['source']['identifier'];
        }
        /** @var DataSourceFactory $dataSourceFactory */
        $dataSourceFactory = $this->factoryFactory->get(DataSourceInterface::class);
        $task->setSource(
            $dataSourceFactory->get($settings['source'], $sourceIdentifier)
        );
    }

    /**
     * Sets the pre processors for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setPreProcessors($task, array $settings, $identifier): void
    {
        $componentClass = PreProcessorInterface::class;
        $components = $this->createComponents($componentClass, $settings, $identifier);
        $task->setPreProcessors($components);
    }

    /**
     * Sets the post processors for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     */
    protected function setPostProcessors($task, array $settings, $identifier): void
    {
        $components = $this->createComponents(
            PostProcessorInterface::class,
            $settings,
            $identifier
        );

        $task->setPostProcessors($components);
    }

    /**
     * Sets the converters for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     */
    protected function setConverters($task, array $settings, $identifier): void
    {
        $components = $this->createComponents(
            ConverterInterface::class,
            $settings,
            $identifier
        );
        $task->setConverters($components);
    }

    /**
     * Sets the finishers for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setFinishers($task, array $settings, $identifier): void
    {
        $componentClass = FinisherInterface::class;
        $components = $this->createComponents(
            FinisherInterface::class,
            $settings,
            $identifier
        );
        $task->setFinishers($components);
    }

    /**
     * Sets the initializers for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setInitializers($task, array $settings, $identifier): void
    {
        $components = $this->createComponents(
            InitializerInterface::class,
            $settings,
            $identifier
        );
        $task->setInitializers($components);
    }

    /**
     * @param string $componentClass
     * @param array $settings
     * @param string $identifier
     * @return array
     */
    protected function createComponents(string $componentClass, array $settings, string $identifier): array
    {
        $factory = $this->factoryFactory->get($componentClass);
        $components = [];
        foreach ($settings as $key => $singleSettings) {
            $instance = $factory->get($singleSettings, $identifier);
            if (isset($singleSettings['config'])) {
                $instance->setConfiguration($singleSettings['config']);
            }
            $components[$key] = $instance;
        }
        return $components;
    }
}
