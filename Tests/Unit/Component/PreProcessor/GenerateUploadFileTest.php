<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

/**
 * Copyright notice
 * (c) 2017. Dirk Wenzel <wenzel@cps-it.de>
 * All rights reserved
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Component\PreProcessor\GenerateUploadFile;
use CPSIT\T3importExport\Resource\AllowedHosts;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use CPSIT\T3importExport\Factory\FilePathFactory;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateUploadFileTest
 */
class GenerateUploadFileTest extends TestCase
{
    /**
     * @var GenerateUploadFile|MockObject
     */
    protected $subject;

    /**
     * @var ResourceStorage|MockObject
     */
    protected $resourceStorage;

    /**
     * @var StorageRepository|MockObject
     */
    protected $storageRepository;

    /**
     * @var FilePathFactory|MockObject
     */
    protected $filePathFactory;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(GenerateUploadFile::class)
            ->onlyMethods(['getAbsoluteTargetPath', 'resolveSourceFile'])->getMock();
        $this->subject->method('resolveSourceFile')->willReturnCallback(
            static fn(string $path): string => vfsStream::url('root') . '/' . $path
        );

        $this->resourceStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfiguration'])->getMock();

        $this->subject->withStorage($this->resourceStorage);

        $this->filePathFactory = $this->getMockBuilder(FilePathFactory::class)
            ->onlyMethods(['createFromParts'])->getMock();

        $this->subject->injectFilePathFactory($this->filePathFactory);
    }

    /**
     * Provides dependencies for injection tests
     */
    public static function dependenciesDataProvider()
    {
        return [
            [StorageRepository::class, 'storageRepository'],
        ];
    }

    /**
     * @param string $class Class name of the dependency to inject
     * @param string $propertyName The property holding the dependency
     */
    #[Test]
    #[DataProvider('dependenciesDataProvider')]
    public function dependenciesCanBeInjected($class, $propertyName)
    {
        $mockDependency = $this->getMockBuilder($class)->disableOriginalConstructor()
            ->getMock();

        $methodName = 'inject' . ucfirst($propertyName);
        $this->subject->{$methodName}($mockDependency);

        // Use reflection to access the protected property
        $reflection = new \ReflectionClass($this->subject);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        $this->assertSame(
            $mockDependency,
            $property->getValue($this->subject)
        );
    }

    #[Test]
    public function getFileReturnsEmptyStringWhenCopyFails()
    {
        // Set up vfsStream
        vfsStreamWrapper::register();
        $rootDirectory = 'root';
        $targetDirectory = 'targetDir';

        // Create virtual file system with only target directory (no source file)
        $fileStructure = [
            $targetDirectory => [],
        ];
        vfsStream::setup($rootDirectory, null, $fileStructure);

        $configuration = [
            'targetDirectoryPath' => $targetDirectory,
        ];

        // Non-existent source file path
        $nonExistentSourceFilePath = 'nonexistent/file.txt';

        $storageConfiguration = ['basePath' => vfsStream::url($rootDirectory)];
        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($storageConfiguration);

        // Mock the file path factory to return the expected directory path
        $expectedDirectoryPath = vfsStream::url($rootDirectory) . DIRECTORY_SEPARATOR . $targetDirectory . DIRECTORY_SEPARATOR;
        $this->filePathFactory->expects($this->once())
            ->method('createFromParts')
            ->with([vfsStream::url($rootDirectory), $targetDirectory])
            ->willReturn($expectedDirectoryPath);

        // The target path includes the filename as returned by getTargetPath method
        $expectedTargetPath = $expectedDirectoryPath . 'file.txt';

        // Mock getAbsoluteTargetPath to return the same path for file operations
        $this->subject->expects($this->once())
            ->method('getAbsoluteTargetPath')
            ->with($expectedTargetPath)
            ->willReturn($expectedTargetPath);

        // Test that when copy fails (source file doesn't exist), empty string is returned
        $result = $this->subject->getFile($configuration, $nonExistentSourceFilePath);

        $this->assertSame('', $result);
    }

    #[Test]
    public function getFileCopiesFileToTarget()
    {
        // Set up vfsStream
        vfsStreamWrapper::register();
        [$rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure] = $this->mockFileStructure();

        // Create the virtual file system
        vfsStream::setup($rootDirectory, null, $fileStructure);

        $storageConfiguration = ['basePath' => vfsStream::url($rootDirectory)];
        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($storageConfiguration);

        // Mock the file path factory to return the expected directory path
        $expectedDirectoryPath = vfsStream::url($rootDirectory) . DIRECTORY_SEPARATOR . $targetDirectory . DIRECTORY_SEPARATOR;
        $this->filePathFactory->expects($this->once())
            ->method('createFromParts')
            ->with([vfsStream::url($rootDirectory), $targetDirectory])
            ->willReturn($expectedDirectoryPath);

        // The target path includes the filename as returned by getTargetPath method
        $expectedTargetPath = $expectedDirectoryPath . $sourceFileName;

        // Mock getAbsoluteTargetPath to return the same path for file operations
        $this->subject->expects($this->once())
            ->method('getAbsoluteTargetPath')
            ->with($expectedTargetPath)
            ->willReturn($expectedTargetPath);

        $result = $this->subject->getFile($configuration, $sourceFilePath);

        // Assert that the method returns the expected path
        $this->assertSame($expectedTargetPath, $result);

        // Assert that the file was actually copied
        $this->assertFileExists($expectedTargetPath);
    }

    /**
     * A subject that applies the file rules: sources only from var/import/tests,
     * URLs only from data.example.org
     */
    protected function createSubjectWithFileRules(): GenerateUploadFile
    {
        $subject = new GenerateUploadFile();
        $subject->withStorage($this->resourceStorage);
        $subject->injectFilePathFactory($this->filePathFactory);
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');
        $subject->setResourceAccess(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, $this->createStub(RequestFactoryInterface::class), ['data.example.org']),
            new AllowedHosts(['data.example.org'])
        ));
        $this->resourceStorage->method('getConfiguration')->willReturn(['basePath' => 'fileadmin/']);
        $this->filePathFactory->method('createFromParts')->willReturn('fileadmin/target/');

        return $subject;
    }

    #[Test]
    public function getFileRejectsSourceFileOutsideOfTheAllowedDirectories(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->createSubjectWithFileRules()->getFile(['targetDirectoryPath' => 'target'], 'typo3conf/secret.txt');
    }

    #[Test]
    public function getFileRejectsSourceUrlOfHostThatIsNotAllowed(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED);

        $this->createSubjectWithFileRules()->getFile(['targetDirectoryPath' => 'target'], 'https://other.example.org/image.jpg');
    }

    #[Test]
    public function getFileRejectsTargetFileTypeThatIsNotAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED);

        $this->createSubjectWithFileRules()->getFile(['targetDirectoryPath' => 'target'], Environment::getVarPath() . '/import/tests/image.php');
    }

    #[Test]
    public function getFilePassesTheSourcePathOfTheConfigurationToTheSourceCheck(): void
    {
        $subject = $this->createSubjectWithFileRules();
        $resourceAccess = $this->createMock(ResourceAccess::class);
        $resourceAccess->expects(self::once())->method('resolveSourceFile')
            ->with('EXT:t3import_export/Resources/Public/ext_icon.gif', 'EXT:t3import_export/Resources/Public/')
            ->willThrowException(new InvalidArgumentException('not allowed', Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED));
        $subject->setResourceAccess($resourceAccess);

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        $subject->getFile(
            ['targetDirectoryPath' => 'target', 'sourcePath' => 'EXT:t3import_export/Resources/Public/'],
            'EXT:t3import_export/Resources/Public/ext_icon.gif'
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notAllowedTargetFileNameDataProvider(): iterable
    {
        yield 'server configuration file' => ['.user.ini'];
        yield 'hash' => ['image#1.jpg'];
        yield 'question mark' => ['image?.jpg'];
        yield 'colon' => ['image:1.jpg'];
        yield 'trailing dot' => ['image.jpg.'];
    }

    #[Test]
    #[DataProvider('notAllowedTargetFileNameDataProvider')]
    public function getFileRejectsTargetFileNameThatIsNotAllowed(string $fileName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_NAME_NOT_ALLOWED);

        $this->createSubjectWithFileRules()->getFile(['targetDirectoryPath' => 'target'], Environment::getVarPath() . '/import/tests/' . $fileName);
    }

    #[Test]
    public function getFileTakesTheTargetFileNameOfAUrlFromItsPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED);

        $this->createSubjectWithFileRules()->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/download.php?file=image.jpg');
    }

    #[Test]
    public function getFileWritesUrlWithQueryAndFragmentToTheNameOfItsPath(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([new Response(200, [], 'image data')], $requestedUrls));

        $result = $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg?size=large#top');

        self::assertSame('fileadmin/target/image.jpg', $result);
        self::assertSame('image data', file_get_contents(vfsStream::url('root/target/image.jpg')));
    }

    /**
     * A subject that loads URLs of data.example.org and cdn.example.org with
     * the given client and writes to vfs://root/target/<file name>
     */
    protected function createSubjectLoadingUrls(ClientInterface $client, array $targetFiles = []): GenerateUploadFile
    {
        vfsStream::setup('root', null, ['target' => $targetFiles]);
        $subject = new class () extends GenerateUploadFile {
            protected function getAbsoluteTargetPath(string $targetPath): string
            {
                return vfsStream::url('root/target/' . basename($targetPath));
            }
        };
        $subject->withStorage($this->resourceStorage);
        $subject->injectFilePathFactory($this->filePathFactory);
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturnCallback(
            static fn(string $method, $uri): Request => new Request($method, $uri)
        );
        $subject->setResourceAccess(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, $requestFactory, ['data.example.org', 'cdn.example.org']),
            new AllowedHosts(['data.example.org', 'cdn.example.org'])
        ));
        $this->resourceStorage->method('getConfiguration')->willReturn(['basePath' => 'fileadmin/']);
        $this->filePathFactory->method('createFromParts')->willReturn('fileadmin/target/');

        return $subject;
    }

    /**
     * @param list<ResponseInterface> $responses
     * @param list<string> $requestedUrls
     */
    protected function createClient(array $responses, array &$requestedUrls): ClientInterface
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

    #[Test]
    public function getFileWritesContentOfAllowedUrlToTarget(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([new Response(200, [], 'image data')], $requestedUrls));

        $result = $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg');

        self::assertSame('fileadmin/target/image.jpg', $result);
        self::assertSame(['https://data.example.org/image.jpg'], $requestedUrls);
        self::assertSame('image data', file_get_contents(vfsStream::url('root/target/image.jpg')));
    }

    #[Test]
    public function getFileFollowsRedirectToAllowedHost(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([
            new Response(302, ['Location' => 'https://cdn.example.org/files/image.jpg']),
            new Response(200, [], 'image data'),
        ], $requestedUrls));

        $result = $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg');

        self::assertSame('fileadmin/target/image.jpg', $result);
        self::assertSame(['https://data.example.org/image.jpg', 'https://cdn.example.org/files/image.jpg'], $requestedUrls);
        self::assertSame('image data', file_get_contents(vfsStream::url('root/target/image.jpg')));
    }

    #[Test]
    public function getFileDoesNotFollowRedirectToHostThatIsNotAllowed(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([
            new Response(302, ['Location' => 'https://other.example.org/image.jpg']),
            new Response(200, [], 'other data'),
        ], $requestedUrls));

        try {
            $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg');
            self::fail('Expected exception was not thrown');
        } catch (MissingResourceException $exception) {
            self::assertSame(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED, $exception->getCode());
        }

        self::assertSame(['https://data.example.org/image.jpg'], $requestedUrls);
        self::assertFileDoesNotExist(vfsStream::url('root/target/image.jpg'));
    }

    #[Test]
    public function getFileDoesNotFollowRedirectFromHttpsToHttp(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([
            new Response(301, ['Location' => 'http://data.example.org/image.jpg']),
            new Response(200, [], 'image data'),
        ], $requestedUrls));

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg'));
        self::assertSame(['https://data.example.org/image.jpg'], $requestedUrls);
        self::assertFileDoesNotExist(vfsStream::url('root/target/image.jpg'));
    }

    #[Test]
    public function getFileLoadsUrlWithUppercaseSchemeThroughTheLoader(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([new Response(200, [], 'image data')], $requestedUrls));

        $result = $subject->getFile(['targetDirectoryPath' => 'target'], 'HTTP://data.example.org/image.jpg');

        self::assertSame('fileadmin/target/image.jpg', $result);
        self::assertCount(1, $requestedUrls);
        self::assertSame('http://data.example.org/image.jpg', strtolower($requestedUrls[0]));
        self::assertSame('image data', file_get_contents(vfsStream::url('root/target/image.jpg')));
    }

    #[Test]
    public function getFileDoesNotFollowRedirectToFileUrl(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([
            new Response(302, ['Location' => 'file:///etc/hosts']),
        ], $requestedUrls));

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg'));
        self::assertSame(['https://data.example.org/image.jpg'], $requestedUrls);
        self::assertFileDoesNotExist(vfsStream::url('root/target/image.jpg'));
        self::assertTrue($subject->hasMessageWithId(GenerateUploadFile::WARNING_URL_NOT_LOADED));
    }

    #[Test]
    public function getFileKeepsExistingTargetIfFileCannotBeWritten(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls(
            $this->createClient([new Response(200, [], str_repeat('x', 100))], $requestedUrls),
            ['image.jpg' => 'old content']
        );
        vfsStream::setQuota(50);

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg'));
        self::assertSame('old content', file_get_contents(vfsStream::url('root/target/image.jpg')));
        self::assertSame(['image.jpg'], array_values(array_diff(scandir(vfsStream::url('root/target')) ?: [], ['.', '..'])));
    }

    #[Test]
    public function getFileKeepsExistingTargetIfUrlCannotBeReadCompletely(): void
    {
        $body = FnStream::decorate(Utils::streamFor('image data'), [
            'read' => static fn(): string => throw new \RuntimeException('connection lost'),
        ]);
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls(
            $this->createClient([new Response(200, [], $body)], $requestedUrls),
            ['image.jpg' => 'old content']
        );

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg'));
        self::assertSame('old content', file_get_contents(vfsStream::url('root/target/image.jpg')));
        self::assertSame(['image.jpg'], array_values(array_diff(scandir(vfsStream::url('root/target')) ?: [], ['.', '..'])));
    }

    #[Test]
    public function processDoesNotLetARecordChangeTheHostOfAUrlSourcePath(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([new Response(200, [], 'image data')], $requestedUrls));
        $record = ['image' => '@cdn.example.org/image.jpg'];

        try {
            $subject->process(
                [
                    'targetDirectoryPath' => 'target',
                    'sourceField' => 'image',
                    'targetField' => 'image',
                    'sourcePath' => 'https://data.example.org',
                    'multipleRows' => false,
                ],
                $record
            );
            self::fail('The URL is loaded.');
        } catch (MissingResourceException $exception) {
            self::assertSame(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED, $exception->getCode());
        }
        self::assertSame([], $requestedUrls);
    }

    /**
     * A subject that copies files from vfs://root/source/<file name> to
     * vfs://root/target/<file name>
     *
     * @param array<string, string> $sourceFiles
     * @param array<string, string> $targetFiles
     */
    protected function createSubjectCopyingFiles(array $sourceFiles, array $targetFiles = []): GenerateUploadFile
    {
        vfsStream::setup('root', null, ['source' => $sourceFiles, 'target' => $targetFiles]);
        $subject = new class () extends GenerateUploadFile {
            protected function getAbsoluteTargetPath(string $targetPath): string
            {
                return vfsStream::url('root/target/' . basename($targetPath));
            }

            protected function resolveSourceFile(string $sourceFilePath, ?string $sourcePath = null): string
            {
                return vfsStream::url('root/source/' . basename($sourceFilePath));
            }
        };
        $subject->withStorage($this->resourceStorage);
        $subject->injectFilePathFactory($this->filePathFactory);
        $this->resourceStorage->method('getConfiguration')->willReturn(['basePath' => 'fileadmin/']);
        $this->filePathFactory->method('createFromParts')->willReturn('fileadmin/target/');

        return $subject;
    }

    #[Test]
    public function getFileCopiesLocalFileWithoutTemporaryFilesLeft(): void
    {
        $subject = $this->createSubjectCopyingFiles(['image.jpg' => 'image data'], ['image.jpg' => 'old content']);

        self::assertSame('fileadmin/target/image.jpg', $subject->getFile(['targetDirectoryPath' => 'target'], 'source/image.jpg'));
        self::assertSame('image data', file_get_contents(vfsStream::url('root/target/image.jpg')));
        self::assertSame(['image.jpg'], array_values(array_diff(scandir(vfsStream::url('root/target')) ?: [], ['.', '..'])));
    }

    #[Test]
    public function getFileKeepsExistingTargetIfLocalFileCannotBeWritten(): void
    {
        $subject = $this->createSubjectCopyingFiles(['image.jpg' => str_repeat('x', 100)], ['image.jpg' => 'old content']);
        vfsStream::setQuota(150);

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'source/image.jpg'));
        self::assertSame('old content', file_get_contents(vfsStream::url('root/target/image.jpg')));
        self::assertSame(['image.jpg'], array_values(array_diff(scandir(vfsStream::url('root/target')) ?: [], ['.', '..'])));
    }

    #[Test]
    public function getFileReturnsEmptyStringIfLocalFileDoesNotExist(): void
    {
        $subject = $this->createSubjectCopyingFiles([]);

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'source/image.jpg'));
        self::assertSame([], array_values(array_diff(scandir(vfsStream::url('root/target')) ?: [], ['.', '..'])));
    }

    protected const string SVG = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';

    #[Test]
    public function getFileSanitizesSvgFiles(): void
    {
        $subject = $this->createSubjectCopyingFiles(['image.svg' => self::SVG]);

        self::assertSame('fileadmin/target/image.svg', $subject->getFile(['targetDirectoryPath' => 'target'], 'source/image.svg'));
        $content = (string)file_get_contents(vfsStream::url('root/target/image.svg'));
        self::assertStringNotContainsString('script', $content);
        self::assertStringContainsString('rect', $content);
    }

    #[Test]
    public function getFileSanitizesSvgContentWithOtherFileExtension(): void
    {
        $subject = $this->createSubjectCopyingFiles(['image.txt' => '<?xml version="1.0"?>' . "\n" . self::SVG]);

        $subject->getFile(['targetDirectoryPath' => 'target'], 'source/image.txt');

        $content = (string)file_get_contents(vfsStream::url('root/target/image.txt'));
        self::assertStringNotContainsString('script', $content);
        self::assertStringContainsString('rect', $content);
    }

    #[Test]
    public function getFileSanitizesSvgOfUrl(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([new Response(200, [], self::SVG)], $requestedUrls));

        $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.svg?v=1');

        self::assertStringNotContainsString('script', (string)file_get_contents(vfsStream::url('root/target/image.svg')));
    }

    #[Test]
    public function getFileRemovesTemporaryFileIfSanitizingFails(): void
    {
        $subject = $this->createSubjectCopyingFiles(['image.svg' => self::SVG]);
        $sanitizer = $this->getMockBuilder(SvgSanitizer::class)->onlyMethods(['sanitizeFile'])->getMock();
        $sanitizer->method('sanitizeFile')->willThrowException(new \RuntimeException('failed', 1795300598));
        GeneralUtility::addInstance(SvgSanitizer::class, $sanitizer);

        try {
            $subject->getFile(['targetDirectoryPath' => 'target'], 'source/image.svg');
            self::fail('No exception thrown');
        } catch (\RuntimeException $exception) {
            self::assertSame(1795300598, $exception->getCode());
        } finally {
            GeneralUtility::purgeInstances();
        }
        self::assertSame([], array_values(array_diff(scandir(vfsStream::url('root/target')) ?: [], ['.', '..'])));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function compressedSvgDataProvider(): iterable
    {
        yield 'svgz' => ['image.svgz'];
        yield 'svgz in upper case' => ['image.SVGZ'];
    }

    #[Test]
    #[DataProvider('compressedSvgDataProvider')]
    public function getFileRejectsCompressedSvgFiles(string $fileName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_NAME_NOT_ALLOWED);

        $this->createSubjectWithFileRules()->getFile(['targetDirectoryPath' => 'target'], Environment::getVarPath() . '/import/tests/' . $fileName);
    }

    #[Test]
    public function getFileRejectsUrlThatIsNotAnHttpUrl(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([], $requestedUrls));

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED);

        $subject->getFile(['targetDirectoryPath' => 'target'], 'ftp://data.example.org/image.jpg');
    }

    #[Test]
    public function getFileReturnsEmptyStringIfUrlCannotBeLoaded(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls($this->createClient([new Response(404, [], 'not found')], $requestedUrls));

        self::assertSame('', $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg'));
        self::assertFileDoesNotExist(vfsStream::url('root/target/image.jpg'));
        self::assertTrue($subject->hasMessageWithId(GenerateUploadFile::WARNING_URL_NOT_LOADED));
    }

    /**
     * Creates a mock file structure for testing
     *
     * @return array Array containing [rootDirectory, sourceFileName, sourceFilePath, targetDirectory, configuration, fileStructure]
     */
    protected function mockFileStructure(): array
    {
        $rootDirectory = 'root';
        $sourceFileContent = 'source file content';
        $sourceDirectory = 'sourceDir';
        $sourceFileName = 'foo.csv';
        $sourceFilePath = $sourceDirectory . DIRECTORY_SEPARATOR . $sourceFileName;
        $targetDirectory = 'targetDir';
        $configuration = [
            'targetDirectoryPath' => $targetDirectory,
        ];

        $fileStructure = [
            $sourceDirectory => [
                $sourceFileName => $sourceFileContent,
            ],
            $targetDirectory => [],
        ];
        return [$rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure];
    }
}
