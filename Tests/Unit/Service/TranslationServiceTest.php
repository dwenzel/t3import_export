<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Service\TranslationService;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class DummyDomainObjectA extends AbstractEntity
{
    public DummyDomainObjectA $translationParent;
}

class DummyDomainObjectB extends AbstractEntity
{
}

class TranslationServiceTest extends TestCase
{
    protected TranslationService $subject;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected PersistenceManagerInterface | MockObject $persistenceManager;

    /**
     * @var DataMapper|MockObject
     */
    protected DataMapper|MockObject $dataMapper;

    /**
     * @var DataMap|MockObject
     */
    protected DataMap|MockObject $dataMap;

    /**
     * Set up the subject
     */
    protected function setUp(): void
    {
        $this->mockDataMap()
            ->mockDataMapper();

        // Create persistence manager mock directly
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->subject = new TranslationService($this->dataMapper, $this->persistenceManager);
    }

    protected function mockDataMap(): self
    {
        $this->dataMap = $this->getMockBuilder(DataMap::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTranslationOriginColumnName', 'getColumnMap', 'getClassName', 'getTableName'])
            ->getMock();

        return $this;
    }

    protected function mockDataMapper(): self
    {
        $this->dataMapper = $this->getMockBuilder(DataMapper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDataMap'])
            ->getMock();
        // Don't set up default behavior here - let individual tests configure it

        return $this;
    }

    public function testTranslateThrowsExceptionIfClassesDoNotMatch(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1_432_499_926);
        $objectA = new DummyDomainObjectA();
        $objectB = new DummyDomainObjectB();
        $this->subject->translate($objectA, $objectB, 1);
    }

    public function testTranslateThrowsExceptionIfOrginAndTranslationAreIdentical(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1_432_502_696);
        $objectA = new DummyDomainObjectA();
        $this->subject->translate($objectA, $objectA, 1);
    }

    public function testTranslateThrowsExceptionIfOriginalIsNotTranslatable(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1_432_500_079);
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        $this->dataMapper->expects($this->once())
            ->method('getDataMap')
            ->with($origin::class)
            ->willReturn($this->dataMap);
        $this->dataMap->expects($this->once())
            ->method('getTranslationOriginColumnName')
            ->willReturn(null);

        $this->subject->translate($origin, $translation, 1);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function translateSetsLanguageUid(): void
    {
        $languageUid = 2;
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        
        $this->dataMapper->expects($this->once())
            ->method('getDataMap')
            ->with($origin::class)
            ->willReturn($this->dataMap);
            
        $this->dataMap->expects($this->exactly(2))
            ->method('getTranslationOriginColumnName')
            ->willReturn('l10n_parent');
            
        // Mock the column map that will be created when _setProperty fails
        $columnMap = $this->createMock(ColumnMap::class);
        $this->dataMap->expects($this->once())
            ->method('getColumnMap')
            ->with('l10nParent')
            ->willReturn($columnMap);
            
        $this->dataMap->expects($this->once())
            ->method('getTableName')
            ->willReturn('tx_test_table');
            
        $this->subject->translate($origin, $translation, $languageUid);
        
        // The translation should have the language UID set
        $this->assertSame($languageUid, $translation->_getProperty('_languageUid'));
    }

    #[Test]
    public function translateSetsTranslationOriginal(): void
    {
        $languageUid = 2;
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        
        $this->dataMapper->expects($this->once())
            ->method('getDataMap')
            ->with($origin::class)
            ->willReturn($this->dataMap);
            
        $this->dataMap->expects($this->exactly(2))
            ->method('getTranslationOriginColumnName')
            ->willReturn('l10n_parent');
            
        // Mock the column map that will be created when _setProperty fails
        $columnMap = $this->createMock(ColumnMap::class);
        $this->dataMap->expects($this->once())
            ->method('getColumnMap')
            ->with('l10nParent')
            ->willReturn($columnMap);
            
        $this->dataMap->expects($this->once())
            ->method('getTableName')
            ->willReturn('tx_test_table');
            
        $this->subject->translate($origin, $translation, $languageUid);
        
        // The translation should have the origin set as l10nParent
        $this->assertSame($origin, $translation->_getProperty('l10nParent'));
    }
}
