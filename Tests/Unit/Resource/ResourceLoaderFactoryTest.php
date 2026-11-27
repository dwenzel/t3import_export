<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\T3importExport\Resource\ResourceLoaderFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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

    #[Test]
    public function createdLoaderLoadsUrlOfAllowedHostAndPort(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = ['data.example.org:8443'];
        $requestedUrls = [];
        $subject = new ResourceLoaderFactory(
            $this->createClient([new Response(200, [], 'id')], $requestedUrls),
            $this->createRequestFactory()
        );

        self::assertSame('id', $subject->createHttpResourceLoader()->open('https://data.example.org:8443/news.csv')->getContents());
    }

    #[Test]
    public function createdLoaderDoesNotRequestUrlOfAnotherPort(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = ['data.example.org'];
        $requestedUrls = [];
        $subject = new ResourceLoaderFactory($this->createClient([], $requestedUrls), $this->createRequestFactory());

        try {
            $subject->createHttpResourceLoader()->open('https://data.example.org:8443/news.csv');
            self::fail('The URL is loaded.');
        } catch (MissingResourceException) {
        }
        self::assertSame([], $requestedUrls);
    }

    #[Test]
    public function createdLoaderDoesNotFollowRedirectToAnotherPort(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = ['data.example.org'];
        $requestedUrls = [];
        $subject = new ResourceLoaderFactory(
            $this->createClient([new Response(302, ['Location' => 'https://data.example.org:8443/news.csv'])], $requestedUrls),
            $this->createRequestFactory()
        );

        try {
            $subject->createHttpResourceLoader()->open('https://data.example.org/news.csv');
            self::fail('The redirect is followed.');
        } catch (MissingResourceException) {
        }
        self::assertSame(['https://data.example.org/news.csv'], $requestedUrls);
    }

    /**
     * @param list<ResponseInterface> $responses
     * @param list<string> $requestedUrls
     */
    private function createClient(array $responses, array &$requestedUrls): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(
            static function (RequestInterface $request) use (&$responses, &$requestedUrls): ResponseInterface {
                $requestedUrls[] = (string)$request->getUri();

                return array_shift($responses) ?? throw new \LogicException('unexpected request');
            }
        );

        return $client;
    }

    private function createRequestFactory(): RequestFactory
    {
        $requestFactory = $this->createStub(RequestFactory::class);
        $requestFactory->method('createRequest')->willReturnCallback(
            static fn(string $method, $uri): Request => new Request($method, $uri)
        );

        return $requestFactory;
    }
}
