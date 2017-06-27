<?php

namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Service\TranslationService;
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
    public $translationParent;
}

class DummyDomainObjectB extends AbstractEntity
{
}

class TranslationServiceTest extends UnitTestCase
{
    /**
     * Subject
     *
     * @var TranslationService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Set up the subject
     * @return void
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            TranslationService::class, ['dummy']
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DataMapper
     */
    public function mockDataMapper()
    {
        $mockDataMapper = $this->getMock(
            DataMapper::class, ['getDataMap'], [], '', false
        );
        $this->inject(
            $this->subject,
            'dataMapper',
            $mockDataMapper
        );
        return $mockDataMapper;
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionCode 1432499926
     */
    public function translateThrowsExceptionIfClassesDoNotMatch()
    {
        $objectA = new DummyDomainObjectA();
        $objectB = new DummyDomainObjectB();
        $this->subject->translate($objectA, $objectB, 1);
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionCode 1432502696
     */
    public function translateThrowsExceptionIfOrginAndTranslationAreIdentical()
    {
        $objectA = new DummyDomainObjectA();
        $this->subject->translate($objectA, $objectA, 1);
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionCode 1432500079
     */
    public function translateThrowsExceptionIfOriginalIsNotTranslatable()
    {
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        $mockDataMapper = $this->mockDataMapper();
        $mockDataMap = $this->getMock(
            DataMap::class, ['getTranslationOriginColumnName'], [], '', false
        );

        $mockDataMapper->expects($this->once())
            ->method('getDataMap')
            ->with(get_class($origin))
            ->will($this->returnValue($mockDataMap));
        $mockDataMap->expects($this->once())
            ->method('getTranslationOriginColumnName')
            ->will($this->returnValue(null));

        $this->subject->translate($origin, $translation, 1);
    }

    /**
     * @test
     */
    public function translateSetsLanguageUid()
    {
        $language = 1;
        $this->subject = $this->getAccessibleMock(
            TranslationService::class, ['haveSameClass']
        );

        $translationOriginColumnName = 'fooBar';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($translationOriginColumnName);

        $origin = $this->getMock(DummyDomainObjectA::class);
        $translation = $this->getMock(
            DummyDomainObjectA::class, ['_setProperty']
        );
        $mockDataMapper = $this->mockDataMapper();
        $mockDataMap = $this->getMock(
            DataMap::class, ['getTranslationOriginColumnName'], [], '', false
        );

        $this->subject->expects($this->once())
            ->method('haveSameClass')
            ->will($this->returnValue(true));
        $mockDataMapper->expects($this->once())
            ->method('getDataMap')
            ->will($this->returnValue($mockDataMap));
        $mockDataMap->expects($this->any())
            ->method('getTranslationOriginColumnName')
            ->will($this->returnValue($translationOriginColumnName));
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
    public function translateSetsTranslationOriginal()
    {
        $language = 1;
        $this->subject = $this->getAccessibleMock(
            TranslationService::class, ['haveSameClass']
        );
        $columnMapClassName = 'foo';
        $tableName = 'bar';

        $translationOriginColumnName = 'translation_parent';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($translationOriginColumnName);

        $origin = $this->getMock(DummyDomainObjectA::class);
        $translation = $this->getAccessibleMock(
            DummyDomainObjectA::class, ['_setProperty']
        );
        $mockDataMapper = $this->mockDataMapper();
        $mockDataMap = $this->getMock(
            DataMap::class,
            ['getTranslationOriginColumnName', 'getColumnMap', 'getClassName', 'getTableName'],
            [], '', false
        );
        $mockColumnMap = $this->getMock(
            ColumnMap::class,
            ['setTypeOfRelation', 'setType', 'setChildTableName'], [], '', false
        );

        $this->subject->expects($this->once())
            ->method('haveSameClass')
            ->will($this->returnValue(true));
        $mockDataMapper->expects($this->once())
            ->method('getDataMap')
            ->will($this->returnValue($mockDataMap));
        $mockDataMap->expects($this->any())
            ->method('getTranslationOriginColumnName')
            ->will($this->returnValue($translationOriginColumnName));
        $mockDataMap->expects($this->any())
            ->method('getColumnMap')
            ->will($this->returnValue($mockColumnMap));
        $mockDataMap->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue($columnMapClassName));
        $mockDataMap->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($tableName));
        $mockColumnMap->expects($this->once())
            ->method('setTypeOfRelation')
            ->with(ColumnMap::RELATION_HAS_ONE);
        $mockColumnMap->expects($this->once())
            ->method('setType')
            ->with($columnMapClassName);
        $mockColumnMap->expects($this->once())
            ->method('setChildTableName')
            ->with($tableName);

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
         * see DummyDomainObjectA->translationParent
         */
        $this->assertAttributeSame(
            $origin,
            $propertyName,
            $translation
        );
    }
}
