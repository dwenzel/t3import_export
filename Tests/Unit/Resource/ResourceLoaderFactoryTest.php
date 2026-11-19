<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\T3importExport\Resource\ResourceLoaderFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

#[CoversClass(ResourceLoaderFactory::class)]
class ResourceLoaderFactoryTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    protected function setUp(): void
    {
        $this->configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->configuration;
    }

    /**
     * @return iterable<string, array{mixed, list<string>|null}>
     */
    public static function allowedHostsDataProvider(): iterable
    {
        yield 'not set' => [null, []];
        yield 'not a list' => ['example.org', []];
        yield 'hosts' => [['example.org', '', 1, '*.example.com'], ['example.org', '*.example.com']];
        yield 'all hosts' => [['*'], null];
    }

    #[Test]
    #[DataProvider('allowedHostsDataProvider')]
    public function readsAllowedHostsFromConfiguration(mixed $configured, ?array $expected): void
    {
        if ($configured === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = $configured;
        }

        self::assertSame($expected, ResourceLoaderFactory::allowedHosts());
    }

    #[Test]
    public function createdLoaderRefusesUrlsWithoutAllowedHosts(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']);
        $subject = new ResourceLoaderFactory(
            $this->createStub(ClientInterface::class),
            $this->createStub(RequestFactory::class)
        );

        self::assertFalse($subject->createHttpResourceLoader()->isHostAllowed('https://example.org/news.csv'));
    }
}
