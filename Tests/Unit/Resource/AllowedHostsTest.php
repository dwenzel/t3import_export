<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\T3importExport\Resource\AllowedHosts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllowedHosts::class)]
class AllowedHostsTest extends TestCase
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
     * @return iterable<string, array{list<string>, string, bool}>
     */
    public static function urlDataProvider(): iterable
    {
        yield 'host, default port of https' => [['data.example.org'], 'https://data.example.org/a.jpg', true];
        yield 'host, default port of http' => [['data.example.org'], 'http://data.example.org/a.jpg', true];
        yield 'host, explicit default port' => [['data.example.org'], 'https://data.example.org:443/a.jpg', true];
        yield 'host, other port' => [['data.example.org'], 'https://data.example.org:8443/a.jpg', false];
        yield 'host, port of the other scheme' => [['data.example.org'], 'https://data.example.org:80/a.jpg', false];
        yield 'host, upper case' => [['data.example.org'], 'https://DATA.Example.org/a.jpg', true];
        yield 'host, trailing dot' => [['data.example.org'], 'https://data.example.org./a.jpg', true];
        yield 'other host' => [['data.example.org'], 'https://other.example.org/a.jpg', false];
        yield 'subdomain of host' => [['example.org'], 'https://data.example.org/a.jpg', false];
        yield 'host with port' => [['data.example.org:8443'], 'https://data.example.org:8443/a.jpg', true];
        yield 'host with port, default port' => [['data.example.org:8443'], 'https://data.example.org/a.jpg', false];
        yield 'host with port, other port' => [['data.example.org:8443'], 'https://data.example.org:9443/a.jpg', false];
        yield 'subdomains' => [['*.example.org'], 'https://data.example.org/a.jpg', true];
        yield 'subdomains, other port' => [['*.example.org'], 'https://data.example.org:8080/a.jpg', false];
        yield 'subdomains with port' => [['*.example.org:8080'], 'http://data.example.org:8080/a.jpg', true];
        yield 'subdomains, domain itself' => [['*.example.org'], 'https://example.org/a.jpg', false];
        yield 'subdomains, similar domain' => [['*.example.org'], 'https://data.badexample.org/a.jpg', false];
        yield 'all hosts' => [['*'], 'https://data.example.org:8443/a.jpg', true];
        yield 'no hosts' => [[], 'https://data.example.org/a.jpg', false];
        yield 'IPv6 host' => [['[::1]'], 'http://[::1]/a.jpg', true];
        yield 'IPv6 host with port' => [['[::1]:8080'], 'http://[::1]:8080/a.jpg', true];
        yield 'IPv6 host, other port' => [['[::1]'], 'http://[::1]:8080/a.jpg', false];
        yield 'no host' => [['*'], 'file:///etc/passwd', false];
    }

    /**
     * @param list<string> $hosts
     */
    #[Test]
    #[DataProvider('urlDataProvider')]
    public function checksHostAndPortOfUrls(array $hosts, string $url, bool $expected): void
    {
        self::assertSame($expected, (new AllowedHosts($hosts))->isAllowed($url));
    }

    #[Test]
    public function hostNamesAreTheHostsWithoutPorts(): void
    {
        self::assertSame(
            ['data.example.org', '*.example.com', '[::1]'],
            (new AllowedHosts(['data.example.org', 'Data.example.org:8443', '*.example.com:8080', '[::1]:8080']))->hostNames()
        );
    }

    #[Test]
    public function hostNamesAreNullForAllHosts(): void
    {
        self::assertNull((new AllowedHosts(['data.example.org', '*']))->hostNames());
    }

    #[Test]
    public function readsAllowedHostsFromConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = ['data.example.org:8443', '', 1];

        $subject = AllowedHosts::fromConfiguration();

        self::assertTrue($subject->isAllowed('https://data.example.org:8443/a.jpg'));
        self::assertFalse($subject->isAllowed('https://data.example.org/a.jpg'));
    }

    #[Test]
    public function allowsNoHostWithoutConfiguration(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']);

        self::assertFalse(AllowedHosts::fromConfiguration()->isAllowed('https://data.example.org/a.jpg'));
    }
}
