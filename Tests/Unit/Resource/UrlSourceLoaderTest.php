<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use CPSIT\T3importExport\Resource\UrlSourceLoader;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;

#[CoversClass(UrlSourceLoader::class)]
class UrlSourceLoaderTest extends TestCase
{
    protected RequestFactory&MockObject $requestFactory;

    /**
     * @var list<string>
     */
    protected array $requestedUrls = [];

    protected function setUp(): void
    {
        $this->requestFactory = $this->createMock(RequestFactory::class);
    }

    protected function subject(): UrlSourceLoader
    {
        return new UrlSourceLoader($this->requestFactory, new FileSourceGuard([], ['data.example.org', 'cdn.example.org']));
    }

    /**
     * @param array<string, Response> $responses by URL
     */
    protected function respond(array $responses): void
    {
        $this->requestFactory->method('request')->willReturnCallback(
            function (string $url, string $method, array $options) use ($responses): Response {
                self::assertSame('GET', $method);
                self::assertFalse($options['allow_redirects']);
                $this->requestedUrls[] = $url;

                return $responses[$url] ?? new Response('php://temp', 404);
            }
        );
    }

    protected static function ok(string $body): Response
    {
        $response = new Response();
        $response->getBody()->write($body);
        $response->getBody()->rewind();

        return $response;
    }

    protected static function redirect(string $location, int $status = 302): Response
    {
        return new Response('php://temp', $status, ['Location' => $location]);
    }

    #[Test]
    public function openReturnsBodyOfUrl(): void
    {
        $this->respond(['https://data.example.org/image.jpg' => self::ok('image')]);

        self::assertSame('image', (string)$this->subject()->open('https://data.example.org/image.jpg'));
    }

    #[Test]
    public function openRejectsUrlOfOtherHostWithoutRequest(): void
    {
        $this->requestFactory->expects(self::never())->method('request');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject()->open('http://127.0.0.1:18081/latest/meta-data/');
    }

    #[Test]
    public function openFollowsRedirectToAllowedHost(): void
    {
        $this->respond([
            'https://data.example.org/image.jpg' => self::redirect('https://cdn.example.org/files/image.jpg'),
            'https://cdn.example.org/files/image.jpg' => self::redirect('other.jpg#fragment', 301),
            'https://cdn.example.org/files/other.jpg' => self::ok('image'),
        ]);

        self::assertSame('image', (string)$this->subject()->open('https://data.example.org/image.jpg'));
        self::assertSame(
            [
                'https://data.example.org/image.jpg',
                'https://cdn.example.org/files/image.jpg',
                'https://cdn.example.org/files/other.jpg',
            ],
            $this->requestedUrls
        );
    }

    #[Test]
    public function openRejectsRedirectToOtherHost(): void
    {
        $this->respond([
            'https://data.example.org/image.jpg' => self::redirect('http://127.0.0.1:18081/latest/meta-data/'),
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        try {
            $this->subject()->open('https://data.example.org/image.jpg');
        } finally {
            self::assertSame(['https://data.example.org/image.jpg'], $this->requestedUrls);
        }
    }

    #[Test]
    public function openRejectsRedirectToOtherScheme(): void
    {
        $this->respond([
            'https://data.example.org/image.jpg' => self::redirect('file:///etc/passwd'),
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_URL_NOT_ALLOWED);

        $this->subject()->open('https://data.example.org/image.jpg');
    }

    #[Test]
    public function openDoesNotFollowRedirectFromHttpsToHttp(): void
    {
        $this->respond([
            'https://data.example.org/image.jpg' => self::redirect('http://data.example.org/image.jpg'),
            'http://data.example.org/image.jpg' => self::ok('image'),
        ]);

        self::assertNull($this->subject()->open('https://data.example.org/image.jpg'));
        self::assertSame(['https://data.example.org/image.jpg'], $this->requestedUrls);
    }

    #[Test]
    public function openFollowsAtMostFiveRedirects(): void
    {
        $responses = [];
        for ($i = 0; $i <= UrlSourceLoader::MAX_REDIRECTS; $i++) {
            $responses['https://data.example.org/' . $i] = self::redirect('/' . ($i + 1));
        }
        $responses['https://data.example.org/' . (UrlSourceLoader::MAX_REDIRECTS + 1)] = self::ok('image');
        $this->respond($responses);

        self::assertNull($this->subject()->open('https://data.example.org/0'));
        self::assertCount(UrlSourceLoader::MAX_REDIRECTS + 1, $this->requestedUrls);
    }

    #[Test]
    public function openReturnsNullForErrorStatus(): void
    {
        $this->respond([]);

        self::assertNull($this->subject()->open('https://data.example.org/missing.jpg'));
    }

    #[Test]
    public function openReturnsNullIfRequestFails(): void
    {
        $this->requestFactory->method('request')->willThrowException(
            new ConnectException('failed', new Request('GET', 'https://data.example.org/image.jpg'))
        );

        self::assertNull($this->subject()->open('https://data.example.org/image.jpg'));
    }

    #[Test]
    public function openRejectsRedirectToOtherPort(): void
    {
        $this->respond([
            'http://127.0.0.1:18081/image.jpg' => self::redirect('http://127.0.0.1:18082/image.jpg'),
            'http://127.0.0.1:18082/image.jpg' => self::ok('image'),
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        (new UrlSourceLoader($this->requestFactory, new FileSourceGuard([], ['127.0.0.1:18081'])))
            ->open('http://127.0.0.1:18081/image.jpg');
    }

    #[Test]
    public function openFollowsRedirectToListedPort(): void
    {
        $this->respond([
            'http://127.0.0.1:18081/image.jpg' => self::redirect('http://127.0.0.1:18082/image.jpg'),
            'http://127.0.0.1:18082/image.jpg' => self::ok('image'),
        ]);

        self::assertSame(
            'image',
            (string)(new UrlSourceLoader($this->requestFactory, new FileSourceGuard([], ['127.0.0.1:18081', '127.0.0.1:18082'])))
                ->open('http://127.0.0.1:18081/image.jpg')
        );
    }

    #[Test]
    public function openRejectsRedirectToNonDefaultPortOfHostWithoutPort(): void
    {
        $this->respond([
            'https://data.example.org/image.jpg' => self::redirect('https://data.example.org:8443/image.jpg'),
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject()->open('https://data.example.org/image.jpg');
    }
}
