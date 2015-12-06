<?php
namespace CPSIT\T3import\Service;

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
use CPSIT\T3import\Domain\Model\Dto\DemandInterface;
use CPSIT\T3import\PostProcessor\AbstractPostProcessor;
use CPSIT\T3import\PostProcessor\PostProcessorInterface;
use CPSIT\T3import\PreProcessor\AbstractPreProcessor;
use CPSIT\T3import\Property\PropertyMappingConfigurationBuilder;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\UnknownClassException;
use TYPO3\CMS\Extbase\Persistence\Repository;
use CPSIT\ZewProjectconf\Service\ZewDbConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use CPSIT\T3import\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;

/**
 * Class ImportProcessor
 *
 * @package CPSIT\T3import\Service
 */
class ImportProcessor {

	/**
	 * Source Database
	 *
	 * @var DatabaseConnection
	 */
	protected $sourceDataBase;

	/**
	 * @var \CPSIT\T3import\Service\DatabaseConnectionService
	 */
	protected $connectionService;

	/**
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * TypoScript configuration
	 *
	 * @var \array $typoScriptConfiguration
	 */
	protected $typoScriptConfiguration;

	/**
	 * Pre processors
	 *
	 * @var array
	 */
	protected $preProcessors = [];

	/**
	 * Post processors
	 *
	 * @var array
	 */
	protected $postProcessors = [];

	/**
	 * Queue
	 * Records to import
	 *
	 * @var array
	 */
	protected $queue = [];

	/**
	 * Repositories
	 * An array with keys for each source type
	 *
	 * @var array
	 */
	protected $repositories = [];

	/**
	 * @var PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * Property mapping configuration
	 * An array with keys for each source type
	 *
	 * @var array
	 */
	protected $propertyMappingConfiguration = [];

	/**
	 * Property mapping configuration builder
	 *
	 * @var PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	/**
	 * source types
	 *
	 * @var array
	 */
	protected $sourceTypes = [];

	/**
	 * @param DatabaseConnectionService $connectionService
	 */
	public function injectDatabaseConnectionService(DatabaseConnectionService $connectionService) {
		$this->connectionService = $connectionService;
	}

	/**
	 * injects the object manager
	 *
	 * @param ObjectManager $objectManager
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * injects the configuration manager
	 *
	 * @param ConfigurationManager $configurationManager
	 */
	public function injectConfigurationManager(ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
		$typoScriptSettings = $this->configurationManager->getConfiguration(
			ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
			'T3import'
		);
		if (isset($typoScriptSettings['importProcessor'])) {
			$this->typoScriptConfiguration = $typoScriptSettings['importProcessor'];
		}
	}

	/**
	 * injects the property manager
	 *
	 * @param PropertyMapper $propertyMapper
	 */
	public function injectPropertyMapper(PropertyMapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * injects the property manager
	 *
	 * @param PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder
	 */
	public function injectPropertyMappingConfigurationBuilder(PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder) {
		$this->propertyMappingConfigurationBuilder = $propertyMappingConfigurationBuilder;
	}

	/**
	 * injects the persistence manager
	 *
	 * @param PersistenceManager $persistenceManager
	 */
	public function injectPersistenceManager(PersistenceManager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * gets the queue
	 *
	 * @return array
	 */
	public function getQueue() {
		return $this->queue;
	}

	/**
	 * Gets the source types
	 *
	 * @param \CPSIT\T3import\Domain\Model\Dto\DemandInterface
	 * @return array
	 */
	public function getSourceTypes(DemandInterface $demand) {
		if ($tasks = $demand->getTasks()) {
			$this->sourceTypes = $tasks;
		}

		return $this->sourceTypes;
	}

	/**
	 * builds the import queue
	 *
	 * @param \CPSIT\T3import\Domain\Model\Dto\DemandInterface
	 */
	public function buildQueue(DemandInterface $importDemand) {
		$sourceTypes = $this->getSourceTypes($importDemand);
		if ((bool) $sourceTypes) {
			foreach ($sourceTypes as $sourceType) {
				$config = $this->getSourceQueryConfiguration($sourceType);
				$database = $this->connectionService->getDatabase($config['identifier']);
				$recordsToImport = $database->exec_SELECTgetRows(
					$config['fields'],
					$config['table'],
					$config['where'],
					$config['groupBy'],
					$config['orderBy'],
					$config['limit']
				);
				if ((bool) $recordsToImport) {
					$this->queue[$sourceType] = $recordsToImport;
				}
			}
		}
	}

	/**
	 * Gets the configuration for the query to the source database
	 *
	 * @param string $sourceType
	 * @return array
	 * @throws InvalidConfigurationException
	 */
	protected function getSourceQueryConfiguration($sourceType) {
		$queryConfiguration = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => ''
		];

		if (isset($this->typoScriptConfiguration['tasks'][$sourceType]['sourceQueryConfiguration'])) {
			$sourceConfiguration = $this->typoScriptConfiguration['tasks'][$sourceType]['sourceQueryConfiguration'];
			if (is_array($sourceConfiguration)) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$queryConfiguration,
					$sourceConfiguration,
					TRUE,
					FALSE
				);

				return $queryConfiguration;
			}
		}

		throw new InvalidConfigurationException(
			'Source query configuration for task ' . $sourceType . ' is not valid.',
			1449357881
		);
	}

	/**
	 * Gets the mapping configuration
	 *
	 * @param string $type
	 * @return PropertyMappingConfiguration
	 */
	public function getMappingConfiguration($type) {
		if (isset($this->propertyMappingConfiguration[$type])) {
			return $this->propertyMappingConfiguration[$type];
		} elseif (isset($this->typoScriptConfiguration['tasks'][$type]['propertyMapping'])) {
			return $this->getPropertyMappingConfigurationByType($type);
		}
		if (isset($this->propertyMappingConfiguration['default'])) {
			return $this->propertyMappingConfiguration['default'];
		}
		/** @var PropertyMappingConfiguration $propertyMappingConfiguration */
		$propertyMappingConfiguration = $this->objectManager->get(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration'
		);
		$propertyMappingConfiguration->setTypeConverterOptions(
			'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter',
			[
				PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
				PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
			]
		)->skipUnknownProperties();
		$this->propertyMappingConfiguration['default'] = $propertyMappingConfiguration;

		return $propertyMappingConfiguration;
	}

	/**
	 * Processes the queue
	 *
	 * @return array
	 */
	public function process() {
		$result = [];

		foreach ($this->queue as $sourceType => $records) {
			if ((bool) $records) {
				foreach ($records as $record) {
					$this->preProcessSingle($record, $sourceType);
					$convertedRecord = $this->processSingle($record, $sourceType);
					$this->postProcessSingle($convertedRecord, $record, $sourceType);
					$this->persist($record, $convertedRecord, $sourceType);
					$result[] = $convertedRecord;
				}
			}
			$this->persistenceManager->persistAll();
		}

		return $result;
	}

	/**
	 * Pre processes a single record if any preprocessor is configured
	 *
	 * @param array $record
	 * @param string $type Type of record (should match the external table name)
	 */
	protected function preProcessSingle(&$record, $type) {
		$preProcessors = $this->getPreProcessors($type);
		foreach ($preProcessors as $singleProcessor) {
			/** @var AbstractPreProcessor $instance */
			$instance = $singleProcessor['instance'];
			if (!$instance->isDisabled($singleProcessor['config'], $record)) {
				$instance->process($singleProcessor['config'], $record);
			}
		}
	}

	/**
	 * Post processes a single record if any post processor is configured
	 *
	 * @param mixed $convertedRecord
	 * @param array $record
	 * @param string $type Type of record (should match the external table name)
	 */
	protected function postProcessSingle(&$convertedRecord, &$record, $type = 'event') {
		$postProcessors = $this->getPostProcessors($type);
		foreach ($postProcessors as $singleProcessor) {
			/** @var AbstractPostProcessor $instance */
			$instance = $singleProcessor['instance'];
			if (!$instance->isDisabled($singleProcessor['config'], $record)) {
				$instance->process(
					$singleProcessor['config'],
					$convertedRecord,
					$record
				);
			}
		}
	}

	/**
	 * @param string $type
	 * @return array | FALSE Returns an array with configurations or FALSE if none found
	 */
	protected function getPreProcessorConfiguration($type) {
		if (isset($this->typoScriptConfiguration['tasks'][$type]['preProcessors'])
			AND is_array($this->typoScriptConfiguration['tasks'][$type]['preProcessors'])
		) {
			return $this->typoScriptConfiguration['tasks'][$type]['preProcessors'];
		}

		return [];
	}

	/**
	 * Gets the post processor configuration by type
	 *
	 * @param string $type
	 * @return array | FALSE Returns an array with configurations or FALSE if none found
	 */
	protected function getPostProcessorConfiguration($type) {
		if (isset($this->typoScriptConfiguration['tasks'][$type]['postProcessors'])
			AND is_array($this->typoScriptConfiguration['tasks'][$type]['postProcessors'])
		) {
			return $this->typoScriptConfiguration['tasks'][$type]['postProcessors'];
		}

		return [];
	}

	/**
	 * @param $type
	 * @return array | FALSE
	 * @throws InvalidConfigurationException
	 */
	public function getPreProcessors($type) {
		if (isset($this->preProcessors[$type])) {
			return $this->preProcessors[$type];
		}
		$config = $this->getPreProcessorConfiguration($type);
		$preProcessors = [];
		foreach ($config as $key => $singleConfig) {
			$instance = $this->getSinglePreProcessor($type, $singleConfig, $key);
			if (!$instance->isConfigurationValid($singleConfig['config'])) {
				throw new InvalidConfigurationException(
					'Configuration for pre processor ' . $singleConfig['class'] . ' of import type ' . $type
					. ' is not valid.',
					1447427432
				);
			}
			$preProcessors[] = [
				'instance' => $instance,
				'config' => $singleConfig['config']
			];
		}
		$this->preProcessors[$type] = $preProcessors;

		return $preProcessors;
	}

	/**
	 * Gets the post processors by type
	 *
	 * @param $type
	 * @return array | FALSE
	 * @throws InvalidConfigurationException
	 */
	public function getPostProcessors($type) {
		if (isset($this->postProcessors[$type])) {
			return $this->postProcessors[$type];
		}

		$config = $this->getPostProcessorConfiguration($type);
		$postProcessors = [];
		foreach ($config as $key => $singleConfig) {
			$instance = $this->getSinglePostProcessor($type, $singleConfig, $key);
			if (!$instance->isConfigurationValid($singleConfig['config'])) {
				throw new InvalidConfigurationException(
					'Configuration for post processor ' . $singleConfig['class'] . ' of import type ' . $type
					. ' is not valid.',
					1447863304
				);
			}
			$postProcessors[] = [
				'instance' => $instance,
				'config' => $singleConfig['config']
			];
		}
		$this->postProcessors[$type] = $postProcessors;

		return $postProcessors;
	}

	/**
	 * Gets a single PreProcessor instance
	 *
	 * @param $type
	 * @param $singleConfig
	 * @param $key
	 * @return PreProcessorInterface
	 * @throws InvalidConfigurationException
	 */
	protected function getSinglePreProcessor($type, $singleConfig, $key) {
		if (!isset($singleConfig['class'])) {
			throw new InvalidConfigurationException(
				'Missing class in pre processor configuration ' . $key . ' for import type ' . $type,
				1447427020
			);
		}
		$className = $singleConfig['class'];

		if (!class_exists($className)) {
			throw new InvalidConfigurationException(
				'Pre-processor class ' . $className . ' for import type ' . $type
				. ' does not exist.',
				1447427184
			);
		}

		if (!in_array(PreProcessorInterface::class, class_implements($className))) {
			throw new InvalidConfigurationException(
				'Pre-processor class ' . $className . ' for import type ' . $type
				. ' must implement PreProcessorInterface.',
				1447428235
			);
		}

		/** @var PreProcessorInterface $instance */

		return $this->objectManager->get($className);
	}

	/**
	 * Gets a single PostProcessor instance
	 *
	 * @param $type
	 * @param $singleConfig
	 * @param $key
	 * @return PostProcessorInterface
	 * @throws InvalidConfigurationException
	 */
	protected function getSinglePostProcessor($type, $singleConfig, $key) {
		if (!isset($singleConfig['class'])) {
			throw new InvalidConfigurationException(
				'Missing class in post processor configuration ' . $key . ' for import type ' . $type,
				1447864207
			);
		}
		$className = $singleConfig['class'];

		if (!class_exists($className)) {
			throw new InvalidConfigurationException(
				'Post-processor class ' . $className . ' for import type ' . $type
				. ' does not exist.',
				1447864223
			);
		}

		if (!in_array(PostProcessorInterface::class, class_implements($className))) {
			throw new InvalidConfigurationException(
				'Post-processor class ' . $className . ' for import type ' . $type
				. ' must implement PostProcessorInterface.',
				1447864243
			);
		}

		/** @var PostProcessorInterface $instance */

		return $this->objectManager->get($className);
	}

	/**
	 * Converts a record into an object
	 *
	 * @param array $record Record which should be converted
	 * @param string $type Import type
	 * @return mixed The converted object
	 */
	protected function processSingle($record, $type) {
		return $this->propertyMapper->convert(
			$record,
			$this->typoScriptConfiguration['tasks'][$type]['class'],
			$this->getMappingConfiguration($type)
		);
	}

	/**
	 * @param array $record
	 * @param DomainObjectInterface $convertedRecord
	 * @param string $type
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	protected function persist($record, $convertedRecord, $type) {
		$repository = $this->getRepository($type, $convertedRecord);
		if ($record['__identity']) {
			$repository->update($convertedRecord);
		} else {
			$repository->add($convertedRecord);
		}
	}

	/**
	 * Get a repository by type
	 * Repository class is determined by the type of object.
	 * Instances of repositories are stored into $this->repositories
	 *
	 * @param string $type
	 * @param DomainObjectInterface $object
	 * @return Repository
	 * @throws UnknownClassException
	 */
	protected function getRepository($type, $object) {
		if (!isset($this->repositories[$type])) {
			$objectClass = get_class($object);
			$repositoryClass = str_replace('Model', 'Repository', $objectClass) . 'Repository';
			if (class_exists($repositoryClass)) {
				/** Repository $this->repositories[$type]*/
				$this->repositories[$type] = $this->objectManager->get($repositoryClass);
			} else {
				throw new UnknownClassException();
			}
		}

		return $this->repositories[$type];
	}

	/**
	 * @param $type
	 * @return mixed
	 */
	protected function getPropertyMappingConfigurationByType($type) {
		$this->propertyMappingConfiguration[$type] = $this->propertyMappingConfigurationBuilder->build(
			$this->typoScriptConfiguration['tasks'][$type]['propertyMapping']
		);

		return $this->propertyMappingConfiguration[$type];
	}
}
