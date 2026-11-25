<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use CPSIT\T3importExport\Resource\UrlSourceLoader;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The loader follows redirects itself with the request factory of TYPO3,
 * also if the HTTP settings of TYPO3 enable redirects.
 */
class UrlSourceLoaderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    /**
     * @var resource|null
     */
    protected $server;

    protected int $port = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            self::markTestSkipped('No free port.');
        }
        $this->port = (int)substr((string)strrchr((string)stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $this->server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $this->port, __DIR__ . '/../Fixtures/HttpServer/router.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        ) ?: null;
        for ($i = 0; $i < 50; $i++) {
            $connection = @fsockopen('127.0.0.1', $this->port);
            if ($connection !== false) {
                fclose($connection);
                break;
            }
            usleep(100000);
        }
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allow_redirects'] = ['max' => 5];
    }

    protected function tearDown(): void
    {
        if (\is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        parent::tearDown();
    }

    protected function subject(): UrlSourceLoader
    {
        return new UrlSourceLoader(
            $this->get(RequestFactory::class),
            new FileSourceGuard([], ['127.0.0.1:' . $this->port])
        );
    }

    #[Test]
    public function openFollowsRedirectToAllowedHost(): void
    {
        self::assertSame('target', (string)$this->subject()->open('http://127.0.0.1:' . $this->port . '/redirect'));
    }

    #[Test]
    public function openRejectsRedirectToOtherHostAlthoughRedirectsAreEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject()->open('http://127.0.0.1:' . $this->port . '/redirect-to-other-host');
    }
}
