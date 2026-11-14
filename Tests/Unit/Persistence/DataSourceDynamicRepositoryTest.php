<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceDynamicRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class DataSourceDynamicRepositoryTest extends TestCase
{
    private Typo3QuerySettings $querySettings;
    private QueryInterface&Stub $query;

    protected function setUp(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED, ['0']));
        $this->querySettings = new Typo3QuerySettings($context, $this->createStub(ConfigurationManagerInterface::class));
        $this->query = $this->createStub(QueryInterface::class);
        $this->query->method('getQuerySettings')->willReturn($this->querySettings);
    }

    private function configLanguageIds(array $config): void
    {
        $subject = new DataSourceDynamicRepository();
        (new \ReflectionMethod($subject, 'configLanguageIds'))->invoke($subject, $this->query, $config);
    }

    #[Test]
    public function configLanguageIdsSetsConfiguredLanguageAndKeepsOverlaySettings(): void
    {
        $this->configLanguageIds(['languageId' => '2']);

        $languageAspect = $this->querySettings->getLanguageAspect();
        self::assertFalse($this->querySettings->getRespectSysLanguage());
        self::assertSame(2, $languageAspect->getId());
        self::assertSame(2, $languageAspect->getContentId());
        self::assertSame(LanguageAspect::OVERLAYS_MIXED, $languageAspect->getOverlayType());
        self::assertSame(['0'], $languageAspect->getFallbackChain());
    }

    #[Test]
    public function configLanguageIdsWithoutLanguageIdRespectsSysLanguage(): void
    {
        $this->configLanguageIds([]);

        self::assertTrue($this->querySettings->getRespectSysLanguage());
        self::assertSame(0, $this->querySettings->getLanguageAspect()->getId());
    }
}
