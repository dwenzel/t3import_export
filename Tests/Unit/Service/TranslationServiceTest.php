<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Exception\InvalidColumnMapException;
use CPSIT\T3importExport\Service\TranslationService;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

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
     * Set up the subject
     */
    protected function setUp(): void
    {
        $this->mockDataMapper();

        // Create persistence manager mock directly
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->subject = new TranslationService($this->dataMapper, $this->persistenceManager);
    }

    /**
     * DataMap is final and readonly since TYPO3 14, so the tests use real instances
     */
    protected function createDataMap(?string $translationOriginColumnName): DataMap
    {
        return new DataMap(
            className: DummyDomainObjectA::class,
            tableName: 'tx_dummy_domain_object_a',
            translationOriginColumnName: $translationOriginColumnName,
        );
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
            ->willReturn($this->createDataMap(null));

        $this->subject->translate($origin, $translation, 1);
    }

    /**
     * Lets the data map return the given translation origin column
     */
    protected function expectTranslationOriginColumn(DummyDomainObjectA $origin, string $columnName): void
    {
        $this->dataMapper->expects($this->once())
            ->method('getDataMap')
            ->with($origin::class)
            ->willReturn($this->createDataMap($columnName));
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
        $this->expectTranslationOriginColumn($origin, 'translation_parent');

        $this->subject->translate($origin, $translation, $languageUid);

        self::assertSame($languageUid, $translation->_getProperty('_languageUid'));
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function translateSetsTranslationOriginal(): void
    {
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        $this->expectTranslationOriginColumn($origin, 'translation_parent');

        $this->subject->translate($origin, $translation, 2);

        self::assertSame($origin, $translation->translationParent);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function translateThrowsExceptionIfTranslationOriginPropertyIsMissing(): void
    {
        $origin = new DummyDomainObjectA();
        $translation = new DummyDomainObjectA();
        $this->expectTranslationOriginColumn($origin, 'l10n_parent');

        $this->expectException(InvalidColumnMapException::class);
        $this->expectExceptionCode(TranslationService::MISSING_TRANSLATION_ORIGIN_PROPERTY_EXCEPTION_CODE);
        $this->expectExceptionMessage('has no property "l10nParent" for the translation origin field "l10n_parent"');

        try {
            $this->subject->translate($origin, $translation, 2);
        } finally {
            self::assertNull($translation->_getProperty('_languageUid'));
        }
    }

    #[Test]
    public function getLocalizationParentQueriesDefaultLanguageAndKeepsOverlaySettings(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, ['0']));
        $querySettings = new Typo3QuerySettings($context, $this->createStub(ConfigurationManagerInterface::class));
        $parent = new DummyDomainObjectB();

        $queryResult = $this->createStub(QueryResultInterface::class);
        $queryResult->method('getFirst')->willReturn($parent);
        $query = $this->createStub(QueryInterface::class);
        $query->method('getQuerySettings')->willReturn($querySettings);
        $query->method('matching')->willReturnSelf();
        $query->method('execute')->willReturn($queryResult);
        $this->persistenceManager->method('createQueryForType')->willReturn($query);

        self::assertSame($parent, $this->subject->getLocalizationParent(5, DummyDomainObjectB::class));

        $languageAspect = $querySettings->getLanguageAspect();
        self::assertSame(0, $languageAspect->getId());
        self::assertSame(0, $languageAspect->getContentId());
        self::assertSame(LanguageAspect::OVERLAYS_MIXED, $languageAspect->getOverlayType());
        self::assertSame(['0'], $languageAspect->getFallbackChain());
    }
}
