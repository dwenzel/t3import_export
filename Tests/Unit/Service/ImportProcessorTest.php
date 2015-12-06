<?php
namespace CPSIT\T3import\Tests\Service;

use CPSIT\T3import\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3import\Service\DatabaseConnectionService;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use Webfox\T3events\Domain\Repository\EventRepository;
use Webfox\T3events\Domain\Repository\PersonRepository;
use Webfox\T3events\Domain\Repository\PerformanceRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use CPSIT\T3import\PreProcessor\PreProcessorInterface;
use CPSIT\T3import\PreProcessor\AbstractPreProcessor;
use CPSIT\T3import\Service\ImportProcessor;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

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
 * Class DummyConnectionService
 * replaces external dependency
 *
 * @package CPSIT\T3import\Tests\Service
 */
class DummyConnectionService {
}

/**
 * Class DummyRepository
 * replaces external dependency
 *
 * @package CPSIT\T3import\Tests\Service
 */
class DummyRepository {
}

/**
 * Class DummyInvalidPreProcessor
 * Does not implement PreProcessorInterface
 *
 * @package CPSIT\T3import\Tests\Service
 */
class DummyInvalidPreProcessor {
}

/**
 * Class ImportCommandControllerTest
 *
 * @package CPSIT\T3import\Tests\Service
 * @coversDefaultClass \CPSIT\T3import\Service\ImportProcessor
 */
class ImportProcessorTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Service\ImportProcessor
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy'], [], '', FALSE);
		$mockDataBase = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
			['exec_SELECTgetRows']);
		$this->subject->_set('sourceDataBase', $mockDataBase);
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get']);
		$this->subject->_set('objectManager', $mockObjectManager);
	}

	/**
	 * @test
	 * @covers ::injectDatabaseConnectionService
	 */
	public function injectConnectionServiceForObjectSetsConnectionService() {
		$expectedConnectionService = $this->getAccessibleMock(
			'CPSIT\\T3import\\Service\\DatabaseConnectionService',
			['dummy'], [], '', FALSE);
		$this->subject->injectDatabaseConnectionService($expectedConnectionService);

		$this->assertSame(
			$expectedConnectionService,
			$this->subject->_get('connectionService')
		);
	}

	/**
	 * @test
	 * @covers ::injectObjectManager
	 */
	public function injectObjectManagerForObjectSetsObjectManager() {
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			[], [], '', FALSE);

		$this->subject->injectObjectManager($mockObjectManager);

		$this->assertSame(
			$mockObjectManager,
			$this->subject->_get('objectManager')
		);
	}

	/**
	 * @test
	 * @covers ::injectPropertyMapper
	 */
	public function injectPropertyMapperForObjectSetsPropertyMapper() {
		/** @var PropertyMapper $mockPropertyMapper */
		$mockPropertyMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper',
			[], [], '', FALSE);

		$this->subject->injectPropertyMapper($mockPropertyMapper);

		$this->assertSame(
			$mockPropertyMapper,
			$this->subject->_get('propertyMapper')
		);
	}

	/**
	 * @test
	 * @covers ::injectPropertyMappingConfigurationBuilder
	 */
	public function injectPropertyMappingConfigurationBuilderForObjectSetsPropertyMappingConfigurationBuilder() {
		/** @var PropertyMappingConfigurationBuilder $mockPropertyMappingConfigurationBuilder */
		$mockPropertyMappingConfigurationBuilder = $this->getMock('CPSIT\\T3import\\Property\\PropertyMappingConfigurationBuilder',
			[], [], '', FALSE);

		$this->subject->injectPropertyMappingConfigurationBuilder($mockPropertyMappingConfigurationBuilder);

		$this->assertSame(
			$mockPropertyMappingConfigurationBuilder,
			$this->subject->_get('propertyMappingConfigurationBuilder')
		);
	}

	/**
	 * @test
	 * @covers ::injectConfigurationManager
	 */
	public function injectConfigurationManagerForConfigurationsSetsConfigurationManager() {
		/** @var ConfigurationManager $mockConfigurationManager */
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
			[], [], '', FALSE);

		$this->subject->injectConfigurationManager($mockConfigurationManager);

		$this->assertSame(
			$mockConfigurationManager,
			$this->subject->_get('configurationManager')
		);
	}

	/**
	 * @test
	 * @covers ::injectPersistenceManager
	 */
	public function injectPersistenceManagerForObjectSetsPersistenceManager() {
		/** @var PersistenceManager $mockPersistenceManager */
		$mockPersistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
			[], [], '', FALSE);

		$this->subject->injectPersistenceManager($mockPersistenceManager);

		$this->assertSame(
			$mockPersistenceManager,
			$this->subject->_get('persistenceManager')
		);
	}

	/**
	 * @test
	 * @covers ::getSourceQueryConfiguration
	 */
	public function getSourceQueryConfigurationGetsInitialConfigurationArray() {
		$expectedConfiguration = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => ''
		];
		$sourceType = 'foo';
		$fooConfiguration = [
			'tasks' => [
				$sourceType => [
					'sourceQueryConfiguration' => []
				]
			]
		];
		$this->subject->_set('typoScriptConfiguration', $fooConfiguration);

		$this->assertSame(
			$expectedConfiguration,
			$this->subject->_callRef('getSourceQueryConfiguration', $sourceType)
		);
	}

	/**
	 * @test
	 * @covers ::getSourceQueryConfiguration
	 */
	public function getSourceQueryConfigurationMergesTypoScriptConfiguration() {
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy'], [], '', FALSE);

		$typoScriptConfiguration = [
			'tasks' => [
				'event' => [
					'sourceQueryConfiguration' => [
						'table' => 'fooTable'
					]
				]
			]
		];
		$sourceType = 'event';
		$expectedConfiguration = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => '',
			'table' => 'fooTable'
		];
		$subject->_set('typoScriptConfiguration', $typoScriptConfiguration);

		$this->assertSame(
			$expectedConfiguration,
			$subject->_callRef('getSourceQueryConfiguration', $sourceType)
		);
	}

	/**
	 * @test
	 * @covers ::getQueue
	 */
	public function getQueueForArrayReturnsInitiallyEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getQueue()
		);
	}

	/**
	 * @test
	 * @covers ::getSourceTypes
	 */
	public function getSourceTypesReturnsInitialValue() {
		$mockDemand = $this->getMock(
			'CPSIT\\T3import\\Domain\\Model\\Dto\\DemandInterface'
		);
		$expectedSourceTypes = [];
		$this->assertSame(
			$expectedSourceTypes,
			$this->subject->getSourceTypes($mockDemand)
		);
	}

	/**
	 * @test
	 * @covers ::buildQueue
	 */
	public function buildQueueGetsSourceQueryConfigurationForAllSourceTypes() {
		/** @var ImportProcessor $subject */
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['getSourceQueryConfiguration'], [], '', FALSE);
		$mockDemand = $this->getMock(
			'CPSIT\\T3import\\Domain\\Model\\Dto\\DemandInterface'
		);
		$mockConnectionService = $this->getMock(
			DatabaseConnectionService::class,
			[], [], '', FALSE
		);
		$mockDatabase = $this->getMock(
			DatabaseConnection::class,
			['exec_SELECTgetRows'], [], '', FALSE
		);
		$subject->injectDatabaseConnectionService($mockConnectionService);

		$tasks = ['foo'];

		$mockDemand->expects($this->once())
			->method('getTasks')
			->will($this->returnValue($tasks));
		$queryConf = [
			'identifier' => 'bar'
		];
		$mockConnectionService->expects($this->once())
			->method('getDatabase')
			->will($this->returnValue($mockDatabase));

		$subject->expects($this->once())
			->method('getSourceQueryConfiguration')
			->will($this->returnValue($queryConf));

		$subject->buildQueue($mockDemand);
	}

	/**
	 * @test
	 * @covers ::buildQueue
	 */
	public function buildQueueSetsQueue() {
		/** @var ImportProcessor $subject */
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['getSourceTypes', 'getSourceQueryConfiguration'], [], '', FALSE);

		$mockDemand = $this->getMock(
			'CPSIT\\T3import\\Domain\\Model\\Dto\\DemandInterface'
		);
		$mockConnectionService = $this->getMock(
			DatabaseConnectionService::class,
			[], [], '', FALSE
		);
		$mockDatabase = $this->getMock(
			DatabaseConnection::class,
			['exec_SELECTgetRows'], [], '', FALSE
		);
		$subject->injectDatabaseConnectionService($mockConnectionService);

		$tasks = ['event'];

		$eventResult = ['foo'];
		$expectedQueue = [
			'event' => $eventResult
		];
		$queryConf = [
			'identifier' => 'bar'
		];
		$mockConnectionService->expects($this->once())
			->method('getDatabase')
			->will($this->returnValue($mockDatabase));

		$mockDatabase->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue($eventResult)
			);
		$subject->expects($this->once())
			->method('getSourceTypes')
			->with($mockDemand)
			->will($this->returnValue($tasks));
		$subject->expects($this->once())
			->method('getSourceQueryConfiguration')
			->with('event')
			->will($this->returnValue($queryConf));

		$subject->buildQueue($mockDemand);
		$this->assertSame(
			$expectedQueue,
			$subject->getQueue()
		);
	}


	/**
	 * @test
	 * @covers ::buildQueue
	 */
	public function getSourceQueryConfigurationSetsLimit() {
		$sourceType = 'event';
		$typoScriptConfig = [
			'tasks' => [
				$sourceType => [
					'sourceQueryConfiguration' => [
						'limit' => '5'
					]
				]
			]
		];
		$this->subject->_set('typoScriptConfiguration', $typoScriptConfig);

		$expectedResult = [
			'fields' => '*',
			'where' => '',
			'groupBy' => '',
			'orderBy' => '',
			'limit' => '5'
		];
		$this->assertEquals(
			$expectedResult,
			$this->subject->_call('getSourceQueryConfiguration', $sourceType)
		);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationReturnsInitialPropertyMappingConfiguration() {
		$mockMappingConfiguration = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
			[
				'setTypeConverterOptions',
				'forProperty',
				'forProperties',
				'skipUnknownProperties',
				'allowProperties'
			]
		);
		$mockMappingConfiguration->expects($this->any())
			->method('setTypeConverterOptions')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('skipUnknownProperties')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('forProperty')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('allowProperties')
			->will($this->returnValue($mockMappingConfiguration));

		/** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
		$objectManager = $this->subject->_get('objectManager');

		$objectManager->expects($this->once())
			->method('get')
			->with('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration')
			->will($this->returnValue($mockMappingConfiguration));
		$this->assertSame(
			$mockMappingConfiguration,
			$this->subject->getMappingConfiguration('fooType')
		);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationSetsTypeConverterOptions() {
		$mockMappingConfiguration = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
			[
				'setTypeConverterOptions',
				'forProperty',
				'forProperties',
				'skipUnknownProperties',
				'allowProperties'
			]
		);
		/** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
		$objectManager = $this->subject->_get('objectManager');

		$objectManager->expects($this->once())
			->method('get')
			->with('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('skipUnknownProperties')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('forProperty')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('allowProperties')
			->will($this->returnValue($mockMappingConfiguration));

		$mockMappingConfiguration->expects($this->any())
			->method('setTypeConverterOptions')
			->with(
				'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter',
				[
					PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
					PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
				]
			)
			->will($this->returnValue($mockMappingConfiguration));

		$this->subject->getMappingConfiguration('fooType');
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationSetsAllowedProperties() {
		$mockMappingConfiguration = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
			[
				'setTypeConverterOptions',
				'forProperty',
				'forProperties',
				'skipUnknownProperties',
				'allowProperties'
			]
		);
		$mockMappingConfiguration->expects($this->any())
			->method('setTypeConverterOptions')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('skipUnknownProperties')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('forProperty')
			->will($this->returnValue($mockMappingConfiguration));
		$objectManager = $this->subject->_get('objectManager');

		$objectManager->expects($this->once())
			->method('get')
			->with('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('allowProperties')
			->withConsecutive(
				[
					'headline',
					'subtitle',
					'description',
					'performances',
					'eventType',
					'eventLocation',
					'genre',
					'speakers',
					'zewId',
					'uid',
					'keywords',
					'name',
					'departments',
					'tags',
					'details',
					'personType',
					'shortIdentifier',
					'externalLink',
					'imageFileName',
					'gender',
					'firstName',
					'lastName',
					'details',
					'curriculum',
					'englishCurriculum'
				],
				['zewId'],
				['date', 'endDate']
			)
			->will($this->returnValue($mockMappingConfiguration));

		$this->subject->getMappingConfiguration('fooType');
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationReturnsConfigurationForType() {
		$type = 'fooType';
		$mappingConfiguration = [
			$type => ['bar']
		];

		$this->subject->_set('propertyMappingConfiguration', $mappingConfiguration);
		$this->assertEquals(
			$mappingConfiguration[$type],
			$this->subject->getMappingConfiguration($type)
		);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationReturnsDefaultConfigurationIfSet() {
		$type = 'fooType';
		$mappingConfiguration = [
			'default' => ['baz']
		];

		$this->subject->_set('propertyMappingConfiguration', $mappingConfiguration);
		$this->assertEquals(
			$mappingConfiguration['default'],
			$this->subject->getMappingConfiguration($type)
		);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationBuildsAndReturnsConfigurationForType() {
		$type = 'fooType';
		$typoScriptConfiguration = [
			'tasks' => [
				$type => [
					'propertyMapping' => ['baz']
				]
			]
		];
		$this->subject->_set('typoScriptConfiguration', $typoScriptConfiguration);

		$mockMappingConfigurationBuilder = $this->getMock(
			'CPSIT\\T3import\\Property\\PropertyMappingConfigurationBuilder',
			['build']
		);
		$this->subject->injectPropertyMappingConfigurationBuilder(
			$mockMappingConfigurationBuilder
		);
		$mockMappingConfiguration = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration'
		);

		$mockMappingConfigurationBuilder->expects($this->once())
			->method('build')
			->with($typoScriptConfiguration['tasks'][$type]['propertyMapping'])
			->will($this->returnValue($mockMappingConfiguration));

		$this->assertEquals(
			$mockMappingConfiguration,
			$this->subject->getMappingConfiguration($type)
		);
	}

	/**
	 * @test
	 * @covers ::process
	 */
	public function processDoesNotRunEmptyQueue() {
		/** @var ImportProcessor $subject */
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['processSingle'], [], '', FALSE);

		$subject->expects($this->never())
			->method('processSingle');
		$subject->process();
	}

	/**
	 * @test
	 * @covers ::process
	 */
	public function processDoesRunQueue() {
		/** @var ImportProcessor $subject */
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['processSingle', 'persist', 'postProcessSingle'], [], '', FALSE);
		$mockPersistenceManager = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
			['persistAll']
		);
		$subject->injectPersistenceManager($mockPersistenceManager);
		$sourceTypes = ['event'];
		$subject->_set('sourceTypes', $sourceTypes);
		$singleRecord = ['foo' => 'bar'];
		$queue = [
			'event' => [$singleRecord]
		];
		$subject->_set('queue', $queue);

		$subject->expects($this->once())
			->method('processSingle')
			->with($singleRecord);
		$subject->expects($this->once())
			->method('persist');
		$subject->expects($this->once())
			->method('postProcessSingle');

		$subject->process();
	}

	/**
	 * @test
	 * @covers ::process
	 */
	public function processPreProcessesSingleRecord() {
		/** @var ImportProcessor $subject */
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['processSingle', 'preProcessSingle', 'postProcessSingle', 'persist'], [], '', FALSE);
		$mockPersistenceManager = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
			['persistAll']
		);
		$subject->injectPersistenceManager($mockPersistenceManager);
		$sourceTypes = ['event'];
		$subject->_set('sourceTypes', $sourceTypes);
		$singleRecord = ['foo' => 'bar'];
		$queue = [
			'event' => [$singleRecord]
		];
		$subject->_set('queue', $queue);

		$subject->expects($this->once())
			->method('preProcessSingle')
			->with($singleRecord);

		$subject->process();
	}

	/**
	 * @test
	 * @covers ::preProcessSingle
	 */
	public function preProcessSingleGetsAndExecutesPreProcessors() {
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['getPreProcessors'], [], '', FALSE);
		$singleRecord = ['foo' => 'bar'];
		$queue = [$singleRecord];
		$subject->_set('queue', $queue);
		$mockPreProcessor = $this->getMock(
			PreProcessorInterface::class,
			['process', 'isConfigurationValid', 'isDisabled']
		);
		$type = 'bazType';
		$mockConfiguration = ['foo' => 'bar'];
		$preProcessors = [
			[
				'instance' => $mockPreProcessor,
				'config' => $mockConfiguration
			]
		];

		$subject->expects($this->once())
			->method('getPreProcessors')
			->with($type)
			->will($this->returnValue($preProcessors));
		$mockPreProcessor->expects($this->once())
			->method('process')
			->with($mockConfiguration);

		$subject->_callRef('preProcessSingle', $singleRecord, $type);
	}

	/**
	 * @test
	 * @covers ::getPreProcessors
	 */
	public function getPreProcessorsReturnsPreProcessorsIfSet() {
		$subject = $this->getAccessibleMock(
			'CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy'], [], '', FALSE);
		$type = 'fooType';
		$preProcessors = [
			$type => 'foo'
		];
		$subject->_set('preProcessors', $preProcessors);

		$this->assertSame(
			$preProcessors[$type],
			$subject->_callRef('getPreProcessors', $type)
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1447427020
	 */
	public function getSinglePreProcessorThrowsInvalidConfigurationExceptionIfClassIsNotSet() {
		$type = 'fooType';
		$key = 'bar';
		$configurationWithoutClassName = ['bar'];

		$this->subject->_callRef('getSinglePreProcessor', $type, $configurationWithoutClassName, $key);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1447427184
	 */
	public function getSinglePreProcessorThrowsInvalidConfigurationExceptionIfClassDoesNotExist() {
		$type = 'fooType';
		$key = 'bar';
		$configurationWithNonExistingClass = [
			'class' => 'NonExistingClass'
		];
		$this->subject->_callRef(
			'getSinglePreProcessor',
			$type,
			$configurationWithNonExistingClass,
			$key
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1447428235
	 */
	public function getSinglePreProcessorThrowsExceptionIfClassDoesNotImplementPreProcessorInterface() {
		$type = 'fooType';
		$key = 'bar';
		$configurationWithExistingClass = [
			'class' => 'CPSIT\T3import\Tests\Service\DummyInvalidPreProcessor'
		];
		$this->subject->_callRef(
			'getSinglePreProcessor',
			$type,
			$configurationWithExistingClass,
			$key
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1447427432
	 */
	public function getPreProcessorsThrowsExceptionIfConfigurationIsInvalid() {
		$subject = $this->getAccessibleMock(
			'CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy', 'getSinglePreProcessor'], [], '', FALSE);
		$type = 'fooType';
		$validClass = AbstractPreProcessor::class;
		$configurationWithExistingClass = [
			'1' => [
				'class' => $validClass
			]
		];
		$typoScriptConfiguration = [
			'tasks' => [
				$type => [
					'preProcessors' => $configurationWithExistingClass
				]
			]
		];
		$subject->_set('typoScriptConfiguration', $typoScriptConfiguration);
		$mockPreProcessor = $this->getMock(
			PreProcessorInterface::class,
			['process', 'isConfigurationValid', 'isDisabled']
		);
		$subject->expects($this->once())
			->method('getSinglePreProcessor')
			->will($this->returnValue($mockPreProcessor));
		$mockPreProcessor->expects($this->once())
			->method('isConfigurationValid')
			->will($this->returnValue(FALSE));

		$subject->getPreProcessors($type);
	}

	/**
	 * @test
	 */
	public function getPreProcessorsReturnsPreProcessorsForValidConfiguration() {
		$subject = $this->getAccessibleMock(
			'CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy', 'getSinglePreProcessor', 'getPreProcessorConfiguration'], [], '', FALSE);
		$type = 'fooType';
		$validClass = AbstractPreProcessor::class;
		$validSingleConfiguration = ['foo' => 'bar'];
		$configurationForFooType = [
			'1' => [
				'class' => $validClass,
				'config' => $validSingleConfiguration
			]
		];
		$mockPreProcessor = $this->getMock(
			PreProcessorInterface::class,
			['process', 'isConfigurationValid', 'isDisabled']
		);
		$subject->expects($this->once())
			->method('getPreProcessorConfiguration')
			->will($this->returnValue($configurationForFooType));
		$subject->expects($this->once())
			->method('getSinglePreProcessor')
			->will($this->returnValue($mockPreProcessor));
		$mockPreProcessor->expects($this->once())
			->method('isConfigurationValid')
			->with($validSingleConfiguration)
			->will($this->returnValue(TRUE));
		$expectedResult = [
			[
				'instance' => $mockPreProcessor,
				'config' => $validSingleConfiguration
			]
		];
		$this->assertEquals(
			$expectedResult,
			$subject->getPreProcessors($type)
		);
	}

	/**
	 * @test
	 */
	public function getPreProcessorsInitiallyReturnsEmptyArray() {
		$subject = $this->getAccessibleMock(
			'CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy', 'getPreProcessorConfiguration'], [], '', FALSE);
		$type = 'fooType';
		$configurationForFooType = [];
		$subject->expects($this->once())
			->method('getPreProcessorConfiguration')
			->will($this->returnValue($configurationForFooType));
		$expectedResult = [];
		$this->assertEquals(
			$expectedResult,
			$subject->getPreProcessors($type)
		);
	}

	/**
	 * @test
	 * @covers ::getPreProcessorConfiguration
	 */
	public function getPreProcessorConfigurationReturnsInitiallyEmptyArray() {
		$type = 'foo';
		$this->assertEquals(
			[],
			$this->subject->_callRef('getPreProcessorConfiguration', $type)
		);
	}

	/**
	 * @test
	 * @covers ::getPreProcessorConfiguration
	 */
	public function getPreProcessorConfigurationReturnsConfiguration() {
		$subject = $this->getAccessibleMock('CPSIT\\T3import\\Service\\ImportProcessor',
			['dummy'], [], '', FALSE);
		$type = 'foo';
		$mockConfiguration = ['bar'];
		$typoScriptConfiguration = [
			'tasks' => [
				$type => [
					'preProcessors' => $mockConfiguration
				]
			]
		];
		$subject->_set('typoScriptConfiguration', $typoScriptConfiguration);

		$this->assertSame(
			$mockConfiguration,
			$subject->_callRef('getPreProcessorConfiguration', $type)
		);
	}

	/**
	 * @test
	 * @covers ::processSingle
	 */
	public function processSingleMapsProperties() {
		$subject = $this->getAccessibleMock(
			ImportProcessor::class,
			['getMappingConfiguration']
		);
		$mockPropertyMapper = $this->getMock(
			PropertyMapper::class,
			['convert']
		);
		$type = 'fooType';
		$mockMappingConfiguration = $this->getMock(
			PropertyMappingConfiguration::class,
			[], [], '', FALSE
		);
		$record = [];
		$subject->injectPropertyMapper($mockPropertyMapper);

		$subject->expects($this->once())
			->method('getMappingConfiguration')
			->will($this->returnValue($mockMappingConfiguration));
		$mockPropertyMapper->expects($this->once())
			->method('convert');

		$subject->_callRef('processSingle', $record, $type);
	}
}
