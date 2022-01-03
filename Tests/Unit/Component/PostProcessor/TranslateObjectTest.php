<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\TranslateObject;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use PHPUnit\Framework\TestCase;
use CPSIT\T3importExport\Service\TranslationService;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class TranslateObjectTest extends TestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\PostProcessor\TranslateObject
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            TranslateObject::class, ['dummy', 'emitSignal']
        );
    }

    /**
     * @test
     */
    public function injectTranslationServiceSetsService()
    {
        $mockService = new TranslationService();

        $this->subject->injectTranslationService($mockService);
        $this->assertAttributeSame(
            $mockService,
            'translationService',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectPersistenceManagerSetsPersistenceManager()
    {
        $mockPersistenceManager = $this->getMockForAbstractClass(
            PersistenceManagerInterface::class
        );

        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->assertAttributeSame(
            $mockPersistenceManager,
            'persistenceManager',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectPersistentObjectConverterSetsTypeConverter()
    {
        $mockPersistentObjectConverter = $this->getMockForAbstractClass(
            PersistentObjectConverter::class
        );

        $this->subject->injectPersistentObjectConverter($mockPersistentObjectConverter);
        $this->assertAttributeSame(
            $mockPersistentObjectConverter,
            'typeConverter',
            $this->subject
        );
    }


    /**
     * @test
     */
    public function injectTargetClassConfigurationValidatorSetsValidator()
    {
        $mockValidator = $this->getAccessibleMock(
            TargetClassConfigurationValidator::class
        );
        $this->subject->injectTargetClassConfigurationValidator($mockValidator);

        $this->assertAttributeSame(
            $mockValidator,
            'targetClassConfigurationValidator',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectMappingConfigurationValidatorSetsValidator()
    {
        $mockValidator = $this->getAccessibleMock(
            MappingConfigurationValidator::class
        );
        $this->subject->injectMappingConfigurationValidator($mockValidator);

        $this->assertAttributeSame(
            $mockValidator,
            'mappingConfigurationValidator',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectPropertyMappingConfigurationBuilderSetsBuilder()
    {
        $mockPropertyMappingConfigurationBuilder = $this->getMockForAbstractClass(
            PropertyMappingConfigurationBuilder::class
        );

        $this->subject->injectPropertyMappingConfigurationBuilder($mockPropertyMappingConfigurationBuilder);
        $this->assertAttributeSame(
            $mockPropertyMappingConfigurationBuilder,
            'propertyMappingConfigurationBuilder',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseForMissingParentField()
    {
        $config = [
            'language' => 'foo'
        ];

        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseForMissingLanguageField()
    {
        $config = [
            'parentField' => 'foo'
        ];

        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidValidatesTargetClass()
    {
        $mockTargetClassValidator = $this->getAccessibleMock(
            TargetClassConfigurationValidator::class,
            ['validate']
        );
        $config = [
            'parentField' => 'foo',
            'language' => 'foo',
            'mapping' => [
                'targetClass' => 'bar'
            ]
        ];
        $this->subject->injectTargetClassConfigurationValidator($mockTargetClassValidator);

        $mockTargetClassValidator->expects($this->once())
            ->method('validate')
            ->with($config['mapping'])
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidValidatesMappingConfiguration()
    {
        $mockTargetClassValidator = $this->getAccessibleMock(
            TargetClassConfigurationValidator::class,
            ['validate']
        );
        $mockMappingConfigurationValidator = $this->getAccessibleMock(
            MappingConfigurationValidator::class,
            ['validate']
        );
        $config = [
            'parentField' => 'foo',
            'language' => 'foo',
            'mapping' => [
                'targetClass' => 'bar',
                'config' => ['fooBar']
            ]
        ];
        $this->subject->injectTargetClassConfigurationValidator($mockTargetClassValidator);
        $this->subject->injectMappingConfigurationValidator($mockMappingConfigurationValidator);

        $mockTargetClassValidator->expects($this->once())
            ->method('validate')
            ->with($config['mapping'])
            ->will($this->returnValue(true));
        $mockMappingConfigurationValidator->expects($this->once())
            ->method('validate')
            ->with($config['mapping'])
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $config = [
            'parentField' => 'foo',
            'language' => 'foo',
        ];

        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function processConvertsParentIfParentFieldIsSet()
    {
        $identity = 1;
        $config = [
            'language' => '1',
            'parentField' => 'foo'
        ];
        $mockRecord = [
            'foo' => $identity
        ];

        $targetClass = DomainObjectInterface::class;
        $mockObject = $this->getMock($targetClass);
        $mockParent = $this->getMock($targetClass);

        $mockTranslationService = $this->getMock(
                TranslationService::class, ['translate']);
        $this->subject->injectTranslationService($mockTranslationService);
        $mockPersistenceManager = $this->getMockForAbstractClass(
            PersistenceManagerInterface::class
        );
        $this->subject->injectPersistenceManager($mockPersistenceManager);

        $mockQuerySettings = $this->getMockForAbstractClass(QuerySettingsInterface::class);
        $mockComparison = $this->getMockForAbstractClass(ComparisonInterface::class);
        $mockQueryResult = $this->getMockForAbstractClass(QueryResultInterface::class);
        $mockQuery = $this->getMockForAbstractClass(QueryInterface::class);

        $mockQuery->expects($this->any())
            ->method('getQuerySettings')
            ->will($this->returnValue($mockQuerySettings));
        $mockQuery->expects($this->once())
            ->method('equals')
            ->with('uid', $identity)
            ->will($this->returnValue($mockComparison));
        $mockQuery->expects($this->once())
            ->method('matching')
            ->with($mockComparison)
            ->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($mockQueryResult));
        $mockQuery->expects($this->once())
            ->method('setQuerySettings')
            ->with($mockQuerySettings);
        $mockQueryResult->expects($this->once())
            ->method('getFirst')
            ->will($this->returnValue($mockParent));
        $mockQuerySettings->expects($this->once())
            ->method('setIgnoreEnableFields')
            ->with(true);
        $mockQuerySettings->expects($this->once())
            ->method('setRespectStoragePage')
            ->with(false);
        $mockQuerySettings->expects($this->once())
            ->method('setLanguageUid')
            ->with(0);
        $mockTranslationService->expects($this->once())
                ->method('translate')
                ->with($mockParent, $mockObject, 1);
        $mockPersistenceManager->expects($this->once())
            ->method('createQueryForType')
            ->with(get_class($mockObject))
            ->will($this->returnValue($mockQuery));

        $this->subject->process(
            $config,
            $mockObject,
            $mockRecord
        );
    }
}
