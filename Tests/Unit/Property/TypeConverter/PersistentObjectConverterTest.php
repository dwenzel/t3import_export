<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Property\TypeConverter;

use CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

#[CoversClass(PersistentObjectConverter::class)]
class PersistentObjectConverterTest extends TestCase
{
    private PersistentObjectConverter $subject;
    private PersistenceManagerInterface&Stub $persistenceManager;
    private QueryInterface&Stub $query;
    private Typo3QuerySettings $querySettings;

    protected function setUp(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED, ['0']));
        $this->querySettings = new Typo3QuerySettings($context, $this->createStub(ConfigurationManagerInterface::class));

        $this->query = $this->createStub(QueryInterface::class);
        $this->query->method('getQuerySettings')->willReturn($this->querySettings);
        $this->persistenceManager = $this->createStub(PersistenceManagerInterface::class);
        $this->persistenceManager->method('createQueryForType')->willReturn($this->query);

        $this->subject = new PersistentObjectConverter();
        $this->subject->injectPersistenceManager($this->persistenceManager);
    }

    private function getProperty(string $name): mixed
    {
        return (new \ReflectionProperty($this->subject, $name))->getValue($this->subject);
    }

    private function setConfiguration(?PropertyMappingConfiguration $configuration): void
    {
        (new \ReflectionMethod($this->subject, 'setConfiguration'))->invoke($this->subject, $configuration);
    }

    private function fetchObjectFromPersistence(mixed $identity): object
    {
        return (new \ReflectionMethod($this->subject, 'fetchObjectFromPersistence'))
            ->invoke($this->subject, $identity, \stdClass::class);
    }

    private function queryReturns(?object $object): void
    {
        $queryResult = $this->createStub(QueryResultInterface::class);
        $queryResult->method('getFirst')->willReturn($object);
        $this->query->method('equals')->willReturn($this->createStub(ConstraintInterface::class));
        $this->query->method('matching')->willReturn($this->query);
        $this->query->method('execute')->willReturn($queryResult);
    }

    #[Test]
    public function buildQuerySetsConfiguredLanguageAndKeepsOverlaySettings(): void
    {
        (new \ReflectionProperty($this->subject, 'sysLanguageUid'))->setValue($this->subject, 3);

        (new \ReflectionMethod($this->subject, 'buildQuery'))->invoke($this->subject, \stdClass::class);

        $languageAspect = $this->querySettings->getLanguageAspect();
        self::assertSame(3, $languageAspect->getId());
        self::assertSame(3, $languageAspect->getContentId());
        self::assertSame(LanguageAspect::OVERLAYS_MIXED, $languageAspect->getOverlayType());
        self::assertSame(['0'], $languageAspect->getFallbackChain());
    }

    #[Test]
    public function buildQueryAppliesQuerySettings(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(PersistentObjectConverter::class, [
            PersistentObjectConverter::IGNORE_ENABLE_FIELDS => 1,
            PersistentObjectConverter::ENABLE_FIELDS_TO_BE_IGNORED => ['disabled'],
            PersistentObjectConverter::RESPECT_STORAGE_PAGE => 0,
            PersistentObjectConverter::RESPECT_SYS_LANGUAGE => 0,
            PersistentObjectConverter::INCLUDE_DELETED => true,
            PersistentObjectConverter::STORAGE_PAGE_IDS => [4, 5],
        ]);
        $this->setConfiguration($configuration);

        (new \ReflectionMethod($this->subject, 'buildQuery'))->invoke($this->subject, \stdClass::class);

        self::assertTrue($this->querySettings->getIgnoreEnableFields());
        self::assertSame(['disabled'], $this->querySettings->getEnableFieldsToBeIgnored());
        self::assertFalse($this->querySettings->getRespectStoragePage());
        self::assertFalse($this->querySettings->getRespectSysLanguage());
        self::assertTrue($this->querySettings->getIncludeDeleted());
        self::assertSame([4, 5], $this->querySettings->getStoragePageIds());
    }

    #[Test]
    public function setConfigurationKeepsDefaultsWithoutConfiguration(): void
    {
        $this->setConfiguration(null);
        $this->setConfiguration(new PropertyMappingConfiguration());

        self::assertFalse($this->getProperty('ignoreEnableFields'));
        self::assertSame([], $this->getProperty('enableFieldsToBeIgnored'));
        self::assertTrue($this->getProperty('respectStoragePage'));
        self::assertTrue($this->getProperty('respectSysLanguage'));
        self::assertSame(0, $this->getProperty('sysLanguageUid'));
        self::assertFalse($this->getProperty('includeDeleted'));
        self::assertSame([], $this->getProperty('storagePageIds'));
    }

    public static function listOptionDataProvider(): array
    {
        return [
            'enable fields as string' => [
                PersistentObjectConverter::ENABLE_FIELDS_TO_BE_IGNORED,
                'disabled,starttime',
                'enableFieldsToBeIgnored',
                ['disabled', 'starttime'],
            ],
            'enable fields as array' => [
                PersistentObjectConverter::ENABLE_FIELDS_TO_BE_IGNORED,
                ['endtime'],
                'enableFieldsToBeIgnored',
                ['endtime'],
            ],
            'storage pages as string' => [
                PersistentObjectConverter::STORAGE_PAGE_IDS,
                '3, 7',
                'storagePageIds',
                [3, 7],
            ],
            'storage pages as array' => [
                PersistentObjectConverter::STORAGE_PAGE_IDS,
                [8],
                'storagePageIds',
                [8],
            ],
            'language uid as string' => [
                PersistentObjectConverter::SYS_LANGUAGE_UID,
                '2',
                'sysLanguageUid',
                2,
            ],
        ];
    }

    #[Test]
    #[DataProvider('listOptionDataProvider')]
    public function setConfigurationConvertsOptions(string $option, mixed $value, string $property, mixed $expected): void
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, $option, $value);

        $this->setConfiguration($configuration);

        self::assertSame($expected, $this->getProperty($property));
    }

    #[Test]
    public function fetchObjectFromPersistenceThrowsExceptionForInvalidIdentity(): void
    {
        $this->expectException(InvalidSourceException::class);
        $this->expectExceptionCode(1_297_931_020);

        $this->fetchObjectFromPersistence('foo');
    }

    #[Test]
    public function fetchObjectFromPersistenceReturnsObjectFromPersistenceManager(): void
    {
        $object = new \stdClass();
        $this->persistenceManager->method('getObjectByIdentifier')->willReturn($object);

        self::assertSame($object, $this->fetchObjectFromPersistence(5));
    }

    #[Test]
    public function fetchObjectFromPersistenceThrowsExceptionIfObjectIsNotFoundInStoragePage(): void
    {
        $this->persistenceManager->method('getObjectByIdentifier')->willReturn(null);

        $this->expectException(TargetNotFoundException::class);

        $this->fetchObjectFromPersistence(5);
    }

    #[Test]
    public function fetchObjectFromPersistenceQueriesObjectIfStoragePageIsIgnored(): void
    {
        $object = new \stdClass();
        $this->persistenceManager->method('getObjectByIdentifier')->willReturn(null);
        $this->queryReturns($object);
        (new \ReflectionProperty($this->subject, 'respectStoragePage'))->setValue($this->subject, false);

        self::assertSame($object, $this->fetchObjectFromPersistence('5'));
    }

    #[Test]
    public function fetchObjectFromPersistenceThrowsExceptionIfQueryFindsNoObject(): void
    {
        $this->persistenceManager->method('getObjectByIdentifier')->willReturn(null);
        $this->queryReturns(null);
        (new \ReflectionProperty($this->subject, 'storagePageIds'))->setValue($this->subject, [3]);

        $this->expectException(TargetNotFoundException::class);
        $this->expectExceptionCode(1_297_933_823);

        $this->fetchObjectFromPersistence(5);
    }

    #[Test]
    public function convertFromAppliesConfigurationBeforeFetchingObject(): void
    {
        $object = new \stdClass();
        $this->persistenceManager->method('getObjectByIdentifier')->willReturn(null);
        $this->queryReturns($object);
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(
            PersistentObjectConverter::class,
            PersistentObjectConverter::RESPECT_STORAGE_PAGE,
            false
        );

        self::assertSame($object, $this->subject->convertFrom(5, \stdClass::class, [], $configuration));
        self::assertFalse($this->querySettings->getRespectStoragePage());
    }
}
