<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Resource\AllowedHosts;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\ResourceTrait;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Concrete class for testing ResourceTrait
 */
class ResourceTraitTestClass
{
    use ResourceTrait;
}

/**
 * Class ResourceTraitTest
 */
class ResourceTraitTest extends TestCase
{
    protected ResourceTraitTestClass $subject;

    protected function setUp(): void
    {
        $this->subject = new ResourceTraitTestClass();
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');
        $this->subject->setResourceAccess(new ResourceAccess(
            new Typo3PathResolver(directories: [self::directory()]),
            new HttpResourceLoader($client, $this->createStub(RequestFactoryInterface::class), ['data.example.org']),
            new AllowedHosts(['data.example.org'])
        ));
    }

    protected function tearDown(): void
    {
        foreach (glob(self::directory() . '/resource-*.txt') ?: [] as $file) {
            unlink($file);
        }
    }

    private static function directory(): string
    {
        return Environment::getVarPath() . '/import/tests';
    }

    #[Test]
    public function loadResourceReturnsNullForEmptyConfiguration(): void
    {
        $result = $this->subject->loadResource([]);
        $this->assertNull($result);
    }

    #[Test]
    public function loadResourceReturnsNullForNonExistentFile(): void
    {
        $configuration = ['file' => self::directory() . '/nonexistent.txt'];
        $result = $this->subject->loadResource($configuration);
        $this->assertNull($result);
    }

    #[Test]
    public function loadResourceReturnsContentOfAllowedFile(): void
    {
        GeneralUtility::mkdir_deep(self::directory());
        $file = self::directory() . '/resource-' . uniqid('', true) . '.txt';
        file_put_contents($file, 'content');

        self::assertSame('content', $this->subject->loadResource(['file' => $file]));
    }

    #[Test]
    public function loadResourceRejectsFileOutsideOfTheAllowedDirectories(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject->loadResource(['file' => 'typo3conf/system/settings.php']);
    }

    #[Test]
    public function loadResourceRejectsUrlOfHostThatIsNotAllowed(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED);

        $this->subject->loadResource(['url' => 'https://other.example.org/data.xml']);
    }
}
