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
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

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

    /**
     * Runs the query of the subject with the given configuration on a query mock
     */
    private function fetchResult(QueryInterface $query, array $config): void
    {
        $query->method('getQuerySettings')->willReturn($this->querySettings);
        $query->method('execute')->willReturn($this->createStub(QueryResultInterface::class));
        $repository = $this->createStub(Repository::class);
        $repository->method('createQuery')->willReturn($query);

        $subject = new DataSourceDynamicRepository();
        (new \ReflectionMethod($subject, 'fetchResultWithRepository'))->invoke($subject, $repository, $config);
    }

    #[Test]
    public function constraintsAreJoinedWithAndByDefault(): void
    {
        $title = $this->createStub(ComparisonInterface::class);
        $pid = $this->createStub(ComparisonInterface::class);
        $conjunction = $this->createStub(AndInterface::class);

        $query = $this->createMock(QueryInterface::class);
        $query->method('equals')->willReturnMap([
            ['title', 'foo', true, $title],
            ['pid', '1', true, $pid],
        ]);
        $query->expects($this->once())->method('logicalAnd')
            ->with($title, $pid)
            ->willReturn($conjunction);
        $query->expects($this->once())->method('matching')->with($conjunction);

        $this->fetchResult($query, ['constraints' => ['equals' => ['title' => 'foo', 'pid' => '1']]]);
    }

    #[Test]
    public function constraintsAreJoinedWithOr(): void
    {
        $title = $this->createStub(ComparisonInterface::class);
        $pid = $this->createStub(ComparisonInterface::class);
        $disjunction = $this->createStub(OrInterface::class);

        $query = $this->createMock(QueryInterface::class);
        $query->method('equals')->willReturnMap([
            ['title', 'foo', true, $title],
            ['pid', '1', true, $pid],
        ]);
        $query->expects($this->never())->method('logicalAnd');
        $query->expects($this->once())->method('logicalOr')
            ->with($title, $pid)
            ->willReturn($disjunction);
        $query->expects($this->once())->method('matching')->with($disjunction);

        $this->fetchResult($query, ['constraints' => ['or' => ['equals' => ['title' => 'foo', 'pid' => '1']]]]);
    }

    #[Test]
    public function singleConstraintIsUsedWithoutConjunction(): void
    {
        $title = $this->createStub(ComparisonInterface::class);

        $query = $this->createMock(QueryInterface::class);
        $query->method('equals')->willReturn($title);
        $query->expects($this->never())->method('logicalAnd');
        $query->expects($this->once())->method('matching')->with($title);

        $this->fetchResult($query, ['constraints' => ['equals' => ['title' => 'foo']]]);
    }

    #[Test]
    public function queryWithoutConstraintsHasNoMatching(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $query->expects($this->never())->method('logicalAnd');
        $query->expects($this->never())->method('matching');

        $this->fetchResult($query, []);
    }
}
