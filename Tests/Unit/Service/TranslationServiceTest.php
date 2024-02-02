<?php

namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Service\TranslationService;
use CPSIT\T3importExport\Tests\Unit\Traits\MockPersistenceManagerTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class DummyDomainObjectA extends AbstractEntity
{
    /**
     * @var DummyDomainObjectA
     */
    public DummyDomainObjectA $translationParent;
}

class DummyDomainObjectB extends AbstractEntity
{
}

class TranslationServiceTest extends TestCase
{
    use MockPersistenceManagerTrait;

    protected TranslationService $subject;

    /**
     * @var DataMapper|MockObject
     */
    protected DataMapper $dataMapper;

    /**
     * @var DataMap|MockObject
     */
    protected DataMap $dataMap;

    /**
     * Set up the subject
     * @return void
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->mockDataMap()
            ->mockDataMapper()
            ->mockPersistenceManager();
        $this->subject = new TranslationService($this->dataMapper, $this->persistenceManager);
    }

    protected function mockDataMap(): self
    {
        $this->dataMap = $this->getMockBuilder(DataMap::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTranslationOriginColumnName', 'getColumnMap', 'getClassName', 'getTableName'])
            ->getMock();

        return $this;
    }

    protected function mockDataMapper(): self
    {
        $this->dataMapper = $this->getMockBuilder(DataMapper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataMap'])
            ->getMock();
        $this->dataMapper->method('getDataMap')
            ->willReturn($this->dataMap);

        return $this;
    }

    public function testTranslateThrowsExceptionIfClassesDoNotMatch(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432499926);
        $objectA = new DummyDomainObjectA();
        $objectB = new DummyDomainObjectB();
        $this->subject->translate($objectA, $objectB, 1);
    }

    public function testTranslateThrowsExceptionIfOrginAndTranslationAreIdentical(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432502696);
        $objectA = new DummyDomainObjectA();
        $this->subject->translate($objectA, $objectA, 1);
    }

    public function testTranslateThrowsExceptionIfOriginalIsNotTranslatable(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432500079);
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        $this->dataMapper->expects($this->once())
            ->method('getDataMap')
            ->with(...[$origin::class]);
        $this->dataMap->expects($this->once())
            ->method('getTranslationOriginColumnName')
            ->willReturn(null);

        $this->subject->translate($origin, $translation, 1);
    }

    /**
     * @test
     * @throws Exception
     */
    public function translateSetsLanguageUid(): void
    {
        $language = 1;
        $translationOriginColumnName = 'fooBar';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($translationOriginColumnName);

        $origin = $this->getMockBuilder(DummyDomainObjectA::class)
            ->setMethods(['_setProperty'])
            ->getMock();

        $translation = $this->getMockBuilder(DummyDomainObjectA::class)
            ->setMethods(['_setProperty'])
            ->getMock();

        $this->dataMap->method('getTranslationOriginColumnName')
            ->willReturn($translationOriginColumnName);
        $translation->expects($this->exactly(2))
            ->method(('_setProperty'))
            ->withConsecutive(
                [$propertyName, $origin],
                ['_languageUid', $language]
            );
        $this->subject->translate($origin, $translation, $language);
    }

    /**
     * @test
     */
    public function translateSetsTranslationOriginal(): void
    {
        $language = 1;
        $tableName = 'bar';
        $tableColumnType = new TableColumnType();

        $translationOriginColumnName = 'translation_parent';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($translationOriginColumnName);

        $origin = $this->getMockBuilder(DummyDomainObjectA::class)
            ->setMethods(['_setProperty'])
            ->getMock();
        $translation = $this->getMockBuilder(DummyDomainObjectA::class)
            ->setMethods(['_setProperty'])
            ->getMock();
        $mockColumnMap = $this->getMockBuilder(ColumnMap::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTypeOfRelation', 'setType', 'setChildTableName'])
            ->getMock();

        $this->dataMapper->expects($this->once())
            ->method('getDataMap');
        $this->dataMap->expects($this->atLeastOnce())
            ->method('getTranslationOriginColumnName')
            ->willReturn($translationOriginColumnName);
        $this->dataMap->expects($this->atLeastOnce())
            ->method('getColumnMap')
            ->willReturn($mockColumnMap);
        $this->dataMap->expects($this->atLeastOnce())
            ->method('getTableName')
            ->willReturn($tableName);
        $mockColumnMap->expects($this->once())
            ->method('setTypeOfRelation')
            ->with(...[ColumnMap::RELATION_HAS_ONE]);
        $mockColumnMap->expects($this->once())
            ->method('setType')
            ->with(...[$tableColumnType]);
        $mockColumnMap->expects($this->once())
            ->method('setChildTableName')
            ->with(...[$tableName]);

        $translation->expects($this->exactly(2))
            ->method(('_setProperty'))
            ->withConsecutive(
                [$propertyName, $origin],
                ['_languageUid', $language]
            )
            ->will($this->onConsecutiveCalls(
                false, null
            ));
        $this->subject->translate($origin, $translation, $language);

        /**
         * setting via $translation->{$propertyName} seems to require property being public!
         * @see DummyDomainObjectA::$translationParent
         */
        $this->assertAttributeSame(
            $origin,
            $propertyName,
            $translation
        );
    }
}
