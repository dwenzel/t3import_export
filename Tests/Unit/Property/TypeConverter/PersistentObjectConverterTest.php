<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Property\TypeConverter;

use CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class PersistentObjectConverterTest extends TestCase
{
    #[Test]
    public function buildQuerySetsConfiguredLanguageAndKeepsOverlaySettings(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED, ['0']));
        $querySettings = new Typo3QuerySettings($context, $this->createStub(ConfigurationManagerInterface::class));

        $query = $this->createStub(QueryInterface::class);
        $query->method('getQuerySettings')->willReturn($querySettings);
        $persistenceManager = $this->createStub(PersistenceManagerInterface::class);
        $persistenceManager->method('createQueryForType')->willReturn($query);

        $subject = new PersistentObjectConverter();
        $subject->injectPersistenceManager($persistenceManager);
        (new \ReflectionProperty($subject, 'sysLanguageUid'))->setValue($subject, 3);

        (new \ReflectionMethod($subject, 'buildQuery'))->invoke($subject, \stdClass::class);

        $languageAspect = $querySettings->getLanguageAspect();
        self::assertSame(3, $languageAspect->getId());
        self::assertSame(3, $languageAspect->getContentId());
        self::assertSame(LanguageAspect::OVERLAYS_MIXED, $languageAspect->getOverlayType());
        self::assertSame(['0'], $languageAspect->getFallbackChain());
    }
}
