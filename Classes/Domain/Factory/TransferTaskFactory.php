<?php
namespace CPSIT\T3importExport\Domain\Factory;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Factory\ConverterFactory;
use CPSIT\T3importExport\Component\Factory\FinisherFactory;
use CPSIT\T3importExport\Component\Factory\InitializerFactory;
use CPSIT\T3importExport\Component\Factory\PostProcessorFactory;
use CPSIT\T3importExport\Component\Factory\PreProcessorFactory;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\InvalidConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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
class TransferTaskFactory extends AbstractFactory
{

    /**
     * @var DataTargetFactory
     */
    protected $dataTargetFactory;

    /**
     * @var DataSourceFactory
     */
    protected $dataSourceFactory;

    /**
     * @var PreProcessorFactory
     */
    protected $preProcessorFactory;

    /**
     * @var PostProcessorFactory
     */
    protected $postProcessorFactory;

    /**
     * @var ConverterFactory
     */
    protected $converterFactory;

    /**
     * @var FinisherFactory
     */
    protected $finisherFactory;

    /**
     * @var InitializerFactory
     */
    protected $initializerFactory;


    public function __construct(
        ?ConfigurationManagerInterface $configurationManager = null,
        ?DataTargetFactory $dataTargetFactory = null,
        ?DataSourceFactory $dataSourceFactory = null,
        ?PreProcessorFactory $preProcessorFactory = null,
        ?InitializerFactory $initializerFactory = null,
        ?PostProcessorFactory $postProcessorFactory = null,
        ?ConverterFactory $converterFactory = null,
        ?FinisherFactory $finisherFactory = null
    )
    {
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManager::class);
        $this->dataTargetFactory = $dataTargetFactory ?? GeneralUtility::makeInstance(DataTargetFactory::class);
        $this->dataSourceFactory = $dataSourceFactory ?? GeneralUtility::makeInstance(DataSourceFactory::class);
        $this->preProcessorFactory = $preProcessorFactory ?? GeneralUtility::makeInstance(PreProcessorFactory::class);
        $this->initializerFactory = $initializerFactory ?? GeneralUtility::makeInstance(InitializerFactory::class);
        $this->postProcessorFactory = $postProcessorFactory ?? GeneralUtility::makeInstance(PostProcessorFactory::class);
        $this->converterFactory = $converterFactory ?? GeneralUtility::makeInstance(ConverterFactory::class);
        $this->finisherFactory = $finisherFactory ?? GeneralUtility::makeInstance(FinisherFactory::class);
    }

    /**
     * Builds a task
     *
     * @param array $settings
     * @param string $identifier
     * @return TransferTask
     * @throws InvalidConfigurationException
     * @throws \CPSIT\T3importExport\MissingClassException
     * @throws \CPSIT\T3importExport\MissingInterfaceException
     */
    public function get(array $settings, $identifier = null)
    {
        /** @var TransferTask $task */
        $task = GeneralUtility::makeInstance(TransferTask::class);
        $task->setIdentifier($identifier);

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

        $this->setTarget($task, $settings, $identifier);
        $this->setSource($task, $settings, $identifier);
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

    /**
     * Sets the target for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     * @throws \CPSIT\T3importExport\MissingClassException
     * @throws \CPSIT\T3importExport\MissingInterfaceException
     */
    protected function setTarget(&$task, array $settings, $identifier)
    {
        if (!isset($settings['target'])
            || !is_array(($settings['target']))
        ) {
            throw new InvalidConfigurationException(
                'Invalid configuration for import task ' . $identifier .
                '. Target is missing or is not an array.',
                1451052262
            );
        }
        $targetIdentifier = null;
        if (isset($settings['target']['identifier'])
            && is_string($settings['target']['identifier'])
        ) {
            $targetIdentifier = $settings['target']['identifier'];
        }
        $task->setTarget(
            $this->dataTargetFactory->get($settings['target'], $targetIdentifier)
        );
    }

    /**
     * Sets the source for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     * @throws \CPSIT\T3importExport\MissingClassException
     * @throws \CPSIT\T3importExport\MissingInterfaceException
     */
    protected function setSource(&$task, array $settings, $identifier)
    {
        if (!isset($settings['source'])
            || !is_array(($settings['source']))
        ) {
            throw new InvalidConfigurationException(
                'Invalid configuration for import task ' . $identifier .
                ' Source is missing or is not an array.',
                1451206701
            );
        }
        $sourceIdentifier = null;
        if (isset($settings['source']['identifier'])
            && is_string($settings['source']['identifier'])
        ) {
            $sourceIdentifier = $settings['source']['identifier'];
        }
        $task->setSource(
            $this->dataSourceFactory->get($settings['source'], $sourceIdentifier)
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
    protected function setPreProcessors(&$task, array $settings, $identifier)
    {
        $preProcessors = [];
        foreach ($settings as $key => $singleSettings) {
            /** @var PreProcessorInterface $instance */
            $instance = $this->preProcessorFactory->get($singleSettings, $identifier);
            if (isset($singleSettings['config'])) {
                $instance->setConfiguration($singleSettings['config']);
            }

            $preProcessors[$key] = $instance;
        }
        $task->setPreProcessors($preProcessors);
    }

    /**
     * Sets the post processors for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws \CPSIT\T3importExport\InvalidConfigurationException
     */
    protected function setPostProcessors(&$task, array $settings, $identifier)
    {
        $postProcessors = [];
        foreach ($settings as $key => $singleSettings) {
            /** @var PostProcessorInterface $instance */
            $instance = $this->postProcessorFactory->get($singleSettings, $identifier);
            if (isset($singleSettings['config'])) {
                $instance->setConfiguration($singleSettings['config']);
            }
            $postProcessors[$key] = $instance;
        }
        $task->setPostProcessors($postProcessors);
    }

    /**
     * Sets the converters for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setConverters(&$task, array $settings, $identifier)
    {
        $converters = [];
        foreach ($settings as $key => $singleSettings) {
            /** @var ConverterInterface $instance */
            $instance = $this->converterFactory->get($singleSettings, $identifier);
            if (isset($singleSettings['config'])) {
                $instance->setConfiguration($singleSettings['config']);
            }
            $converters[$key] = $instance;
        }
        $task->setConverters($converters);
    }

    /**
     * Sets the finishers for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setFinishers(&$task, array $settings, $identifier)
    {
        $finishers = [];
        foreach ($settings as $key => $singleSettings) {
            /** @var FinisherInterface $instance */
            $instance = $this->finisherFactory->get($singleSettings, $identifier);
            if (isset($singleSettings['config'])) {
                $instance->setConfiguration($singleSettings['config']);
            }
            $finishers[$key] = $instance;
        }
        $task->setFinishers($finishers);
    }

    /**
     * Sets the initializers for the import task
     *
     * @param TransferTask $task
     * @param array $settings
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setInitializers(&$task, array $settings, $identifier)
    {
        $initializers = [];
        foreach ($settings as $key => $singleSettings) {
            /** @var InitializerInterface $instance */
            $instance = $this->initializerFactory->get($singleSettings, $identifier);
            if (isset($singleSettings['config'])) {
                $instance->setConfiguration($singleSettings['config']);
            }
            $initializers[$key] = $instance;
        }
        $task->setInitializers($initializers);
    }
}
