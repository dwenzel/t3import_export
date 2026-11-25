<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Core\Environment;

#[CoversClass(ResourceAccess::class)]
class ResourceAccessTest extends TestCase
{
    private ResourceAccess $subject;

    protected function setUp(): void
    {
        $this->subject = new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import']),
            new HttpResourceLoader(
                $this->createStub(ClientInterface::class),
                $this->createStub(RequestFactoryInterface::class),
                ['data.example.org']
            )
        );
    }

    #[Test]
    public function tellsWhetherAFileIsAllowed(): void
    {
        self::assertTrue($this->subject->isFileAllowed(Environment::getVarPath() . '/import/news.csv'));
        self::assertFalse($this->subject->isFileAllowed('fileadmin/news.csv'));
        self::assertFalse($this->subject->isFileAllowed(Environment::getVarPath() . '/import/news.php'));
    }

    #[Test]
    public function tellsWhetherAUrlIsAllowed(): void
    {
        self::assertTrue($this->subject->isUrlAllowed('https://data.example.org/news.xml'));
        self::assertFalse($this->subject->isUrlAllowed('https://other.example.org/news.xml'));
        self::assertFalse($this->subject->isUrlAllowed('ftp://data.example.org/news.xml'));
    }

    #[Test]
    public function assertUrlAllowedThrowsExceptionForHostThatIsNotAllowed(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED);

        $this->subject->assertUrlAllowed('https://other.example.org/news.xml');
    }

    #[Test]
    public function openUrlDoesNotFollowRedirectToHostThatIsNotAllowed(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')
            ->willReturn(new Response(302, ['Location' => 'https://other.example.org/news.xml']));
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturnCallback(
            static fn(string $method, $uri): Request => new Request($method, $uri)
        );
        $subject = new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import']),
            new HttpResourceLoader($client, $requestFactory, ['data.example.org'])
        );

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED);

        $subject->openUrl('https://data.example.org/news.xml');
    }

    #[Test]
    public function recognizesUrls(): void
    {
        self::assertTrue(ResourceAccess::isUrl('https://data.example.org/news.xml'));
        self::assertTrue(ResourceAccess::isUrl('ftp://data.example.org/news.xml'));
        self::assertFalse(ResourceAccess::isUrl('fileadmin/news.xml'));
        self::assertFalse(ResourceAccess::isUrl('EXT:foo/news.xml'));
    }
}
