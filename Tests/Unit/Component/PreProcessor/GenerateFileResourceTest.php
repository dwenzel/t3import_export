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
use PHPUnit\Framework\Attributes\DataProvider;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource;
use CPSIT\T3importExport\Resource\AllowedHosts;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\ImportExportCore\LoggingInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateFileResourceTest
 */
class GenerateFileResourceTest extends TestCase
{
    /**
     * @var GenerateFileResource |MockObject
     */
    protected $subject;

    /**
     * @var MessageContainer&MockObject
     */
    protected $messageContainer;

    /**
     * @var FileIndexRepository&MockObject
     */
    protected $fileIndexRepository;

    /**
     * @var ResourceStorage&MockObject
     */
    protected $resourceStorage;

    /**
     * @var FilePathFactory&MockObject
     */
    protected $filePathFactory;

    /**
     * @var StorageRepository&MockObject
     */
    protected $storageRepository;

    /**
     * set up subject
     * @throws vfsStreamException
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->mockFileIndexRepository()
            ->mockResourceStorage()
            ->mockFilePathFactory()
            ->mockStorageRepository();

        // Create message container mock directly
        $this->messageContainer = $this->createMock(MessageContainer::class);

        $this->subject = $this->getMockBuilder(GenerateFileResource::class)
            ->setConstructorArgs(
                [
                    $this->fileIndexRepository,
                    $this->filePathFactory,
                    $this->messageContainer,
                ]
            )
            ->onlyMethods(['logError', 'getAbsoluteTargetPath', 'resolveSourceFile'])
            ->getMock();
        $this->subject->method('resolveSourceFile')->willReturnCallback(
            static fn(string $path): string => vfsStream::url('root') . '/' . $path
        );

        // Set protected properties via reflection
        $reflection = new \ReflectionClass($this->subject);
        $property = $reflection->getProperty('resourceStorage');
        $property->setAccessible(true);
        $property->setValue($this->subject, $this->resourceStorage);

        // Inject the storage repository
        $injectMethod = $reflection->getMethod('injectStorageRepository');
        $injectMethod->setAccessible(true);
        $injectMethod->invoke($this->subject, $this->storageRepository);

        // Skip vfsStream in this test as it's not available
        // We'll use mock methods instead
    }

    /**
     * Mock file index repository
     *
     * @return $this
     */
    protected function mockFileIndexRepository(): self
    {
        $this->fileIndexRepository = $this->createMock(FileIndexRepository::class);
        return $this;
    }

    /**
     * Mock resource storage
     *
     * @return $this
     */
    protected function mockResourceStorage(): self
    {
        $this->resourceStorage = $this->createMock(ResourceStorage::class);
        return $this;
    }

    /**
     * Mock file path factory
     *
     * @return $this
     */
    protected function mockFilePathFactory(): self
    {
        $this->filePathFactory = $this->createMock(FilePathFactory::class);
        return $this;
    }

    /**
     * Mock storage repository
     *
     * @return $this
     */
    protected function mockStorageRepository(): self
    {
        $this->storageRepository = $this->createMock(StorageRepository::class);
        $this->storageRepository->method('findByUid')->willReturn($this->resourceStorage);
        return $this;
    }

    /**
     * Create a new subject with mocked getFile method for process testing
     *
     * @return GenerateFileResource&MockObject
     */
    protected function createSubjectWithMockedGetFile(): LoggingInterface
    {
        $subject = $this->getMockBuilder(GenerateFileResource::class)
            ->setConstructorArgs(
                [
                    $this->fileIndexRepository,
                    $this->filePathFactory,
                    $this->messageContainer,
                ]
            )
            ->onlyMethods(['getFile', 'logError'])
            ->getMock();

        // Set protected properties via reflection
        $reflection = new \ReflectionClass($subject);
        $property = $reflection->getProperty('resourceStorage');
        $property->setAccessible(true);
        $property->setValue($subject, $this->resourceStorage);

        // Inject the storage repository
        $injectMethod = $reflection->getMethod('injectStorageRepository');
        $injectMethod->setAccessible(true);
        $injectMethod->invoke($subject, $this->storageRepository);

        return $subject;
    }

    /**
     * Creates file structure for testing
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

    public function testGetFileReturnsExistingFileFromResourceStorage(): void
    {
        $mockFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();

        $targetDirectoryPath = 'foo/';
        $fileName = 'bar.x';
        $filePath = 'sourcePath/' . $fileName;
        $expectedPath = $targetDirectoryPath . $fileName;

        $configuration = [
            'targetDirectoryPath' => $targetDirectoryPath,
        ];

        $this->resourceStorage->expects($this->once())
            ->method('hasFile')
            ->with(...[$expectedPath])
            ->willReturn(true);

        $this->resourceStorage->expects($this->once())
            ->method('getFile')
            ->with(...[$expectedPath])
            ->willReturn($mockFile);

        $this->assertSame(
            $mockFile,
            $this->subject->getFile($configuration, $filePath)
        );
    }

    public function testGetFileCopiesFileToTarget(): void
    {
        // Set up vfsStream
        vfsStreamWrapper::register();
        [$rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure] = $this->mockFileStructure();

        // Create the virtual file system
        vfsStream::setup($rootDirectory, null, $fileStructure);

        $mockFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();

        // Mock that the file doesn't exist initially - using targetDir/ + basename(sourceFilePath)
        $expectedFilePath = $targetDirectory . '/' . $sourceFileName;
        $this->resourceStorage->expects($this->once())
            ->method('hasFile')
            ->with($expectedFilePath)
            ->willReturn(false);

        $this->mockFileGenerationBehavior($configuration['targetDirectoryPath'], $sourceFileName);

        // Mock the resource storage to return the file after copying
        $this->resourceStorage->expects($this->once())
            ->method('getFile')
            ->with($expectedFilePath)
            ->willReturn($mockFile);

        // Mock the file index repository
        $this->fileIndexRepository->expects($this->once())
            ->method('add')
            ->with($mockFile);

        $this->assertSame($mockFile, $this->subject->getFile($configuration, $sourceFilePath));
    }

    public function testGetFileReturnsNullOnFailure(): void
    {
        // Set up vfsStream
        vfsStreamWrapper::register();
        [$rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure] = $this->mockFileStructure();

        // Create the virtual file system
        vfsStream::setup($rootDirectory, null, $fileStructure);

        // Use an invalid target directory to force copy failure
        $invalidConfiguration = ['targetDirectoryPath' => 'invalidDirectory'];

        // Mock that the file doesn't exist initially
        $expectedFilePath = 'invalidDirectory/' . $sourceFileName;
        $this->resourceStorage->expects($this->once())
            ->method('hasFile')
            ->with($expectedFilePath)
            ->willReturn(false);

        $this->mockFileGenerationBehavior($invalidConfiguration['targetDirectoryPath'], $sourceFileName);

        $this->assertNull($this->subject->getFile($invalidConfiguration, $sourceFilePath));
    }

    /**
     * A subject that loads URLs of data.example.org with the given responses
     * and writes to vfs://root/target/<file name>
     *
     * @param list<ResponseInterface> $responses
     * @param list<string> $requestedUrls
     */
    protected function createSubjectLoadingUrls(array $responses, array &$requestedUrls): GenerateFileResource
    {
        vfsStream::setup('root', null, ['target' => []]);
        $subject = new class ($this->fileIndexRepository, $this->filePathFactory) extends GenerateFileResource {
            protected function getAbsoluteTargetPath(string $targetPath): string
            {
                return vfsStream::url('root/target/' . basename($targetPath));
            }
        };
        $subject->withStorage($this->resourceStorage);
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(
            static function (RequestInterface $request) use (&$responses, &$requestedUrls): ResponseInterface {
                $requestedUrls[] = (string)$request->getUri();

                return array_shift($responses) ?? throw new \LogicException('unexpected request');
            }
        );
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturnCallback(
            static fn(string $method, $uri): Request => new Request($method, $uri)
        );
        $subject->setResourceAccess(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, $requestFactory, ['data.example.org']),
            new AllowedHosts(['data.example.org'])
        ));
        $this->resourceStorage->method('getConfiguration')->willReturn(['basePath' => 'fileadmin/']);
        $this->filePathFactory->method('createFromParts')->willReturn('fileadmin/target/');

        return $subject;
    }

    /**
     * A subject that applies the file rules, with a file of the same name in
     * the target directory: sources only from var/import/tests, URLs only
     * from data.example.org
     */
    protected function createSubjectWithExistingFile(): GenerateFileResource
    {
        $subject = new GenerateFileResource($this->fileIndexRepository, $this->filePathFactory);
        $subject->withStorage($this->resourceStorage);
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');
        $subject->setResourceAccess(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, $this->createStub(RequestFactoryInterface::class), ['data.example.org']),
            new AllowedHosts(['data.example.org'])
        ));
        $this->resourceStorage->method('getConfiguration')->willReturn(['basePath' => 'fileadmin/']);
        $this->filePathFactory->method('createFromParts')->willReturn('fileadmin/target/');
        $this->resourceStorage->method('hasFile')->willReturn(true);
        $this->resourceStorage->expects(self::never())->method('getFile');

        return $subject;
    }

    /**
     * @return iterable<string, array{array<string, string>, string, class-string<\Throwable>, int}>
     */
    public static function notAllowedSourceOfExistingFileDataProvider(): iterable
    {
        yield 'target name' => [
            ['sourcePath' => 'fileadmin/'],
            'fileadmin/image#1.jpg',
            InvalidArgumentException::class,
            Typo3PathResolver::ERROR_FILE_NAME_NOT_ALLOWED,
        ];
        yield 'active content' => [
            ['sourcePath' => 'fileadmin/'],
            'fileadmin/page.html',
            InvalidArgumentException::class,
            Typo3PathResolver::ERROR_FILE_NAME_NOT_ALLOWED,
        ];
        yield 'local file without source path' => [
            [],
            'fileadmin/image.jpg',
            InvalidArgumentException::class,
            Typo3PathResolver::ERROR_NOT_ALLOWED,
        ];
        yield 'local file outside of the allowed directories' => [
            ['sourcePath' => 'typo3conf/'],
            'typo3conf/image.jpg',
            InvalidArgumentException::class,
            Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED,
        ];
        yield 'host that is not allowed' => [
            [],
            'https://other.example.org/image.jpg',
            MissingResourceException::class,
            HttpResourceLoader::ERROR_HOST_NOT_ALLOWED,
        ];
    }

    /**
     * @param array<string, string> $configuration
     * @param class-string<\Throwable> $exceptionClass
     */
    #[Test]
    #[DataProvider('notAllowedSourceOfExistingFileDataProvider')]
    public function getFileChecksTheSourceBeforeUsingAnExistingFile(
        array $configuration,
        string $source,
        string $exceptionClass,
        int $code
    ): void {
        $subject = $this->createSubjectWithExistingFile();

        $this->expectException($exceptionClass);
        $this->expectExceptionCode($code);

        $subject->getFile($configuration + ['targetDirectoryPath' => 'target'], $source);
    }

    #[Test]
    public function getFileChecksTheTargetNameIfTheStorageIsOutsideOfTheProject(): void
    {
        $subject = new GenerateFileResource($this->fileIndexRepository, $this->filePathFactory);
        $subject->withStorage($this->resourceStorage);
        $subject->setResourceAccess(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($this->createStub(ClientInterface::class), $this->createStub(RequestFactoryInterface::class), []),
            new AllowedHosts([])
        ));
        // the base path of the storage cannot be resolved to a path of the project
        $this->resourceStorage->method('getConfiguration')->willReturn(['basePath' => '/outside-of-the-project/']);
        $this->filePathFactory->method('createFromParts')->willReturn('/outside-of-the-project/target/');
        $this->resourceStorage->method('hasFile')->willReturn(true);
        $this->resourceStorage->expects(self::never())->method('getFile');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_NAME_NOT_ALLOWED);

        $subject->getFile(
            ['targetDirectoryPath' => 'target', 'sourcePath' => Environment::getVarPath() . '/import/tests/'],
            Environment::getVarPath() . '/import/tests/.user.ini'
        );
    }

    #[Test]
    public function getFileAddsFileLoadedFromAllowedUrl(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls([new Response(200, [], 'image data')], $requestedUrls);
        $file = $this->createStub(File::class);
        $this->resourceStorage->expects(self::once())->method('hasFile')->with('target/image.jpg')->willReturn(false);
        $this->resourceStorage->expects(self::once())->method('getFile')->with('target/image.jpg')->willReturn($file);
        $this->fileIndexRepository->expects(self::once())->method('add')->with($file);

        self::assertSame($file, $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg'));
        self::assertSame(['https://data.example.org/image.jpg'], $requestedUrls);
        self::assertSame('image data', file_get_contents(vfsStream::url('root/target/image.jpg')));
    }

    #[Test]
    public function getFileDoesNotFollowRedirectToHostThatIsNotAllowed(): void
    {
        $requestedUrls = [];
        $subject = $this->createSubjectLoadingUrls([
            new Response(302, ['Location' => 'https://other.example.org/image.jpg']),
            new Response(200, [], 'other data'),
        ], $requestedUrls);
        $this->resourceStorage->expects(self::once())->method('hasFile')->willReturn(false);
        $this->resourceStorage->expects(self::never())->method('getFile');
        $this->fileIndexRepository->expects(self::never())->method('add');

        try {
            $subject->getFile(['targetDirectoryPath' => 'target'], 'https://data.example.org/image.jpg');
            self::fail('Expected exception was not thrown');
        } catch (MissingResourceException $exception) {
            self::assertSame(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED, $exception->getCode());
        }

        self::assertSame(['https://data.example.org/image.jpg'], $requestedUrls);
        self::assertFileDoesNotExist(vfsStream::url('root/target/image.jpg'));
    }

    /**
     * @param string $rootDirectory
     * @param array $fileStructure
     */
    protected function mockFileGenerationBehavior($targetDirectoryPath, string $sourceFileName): void
    {
        $storageConfiguration = [
            'basePath' => vfsStream::url('root'),
        ];

        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($storageConfiguration);

        $expectedFilePath = $storageConfiguration['basePath'] . DIRECTORY_SEPARATOR . $targetDirectoryPath . DIRECTORY_SEPARATOR . $sourceFileName;

        $this->filePathFactory->expects($this->once())
            ->method('createFromParts')
            ->with([$storageConfiguration['basePath'], $targetDirectoryPath])
            ->willReturn($storageConfiguration['basePath'] . DIRECTORY_SEPARATOR . $targetDirectoryPath . DIRECTORY_SEPARATOR);

        // checked before an existing file is used and again when copying
        $this->subject->expects($this->atLeastOnce())
            ->method('getAbsoluteTargetPath')
            ->with(...[$expectedFilePath])
            ->willReturn($expectedFilePath);
    }

    public function testProcessGetsSingleFile(): void
    {
        $sourceField = 'sourceField';
        $targetField = 'targetField';
        $sourceFilePath = 'path/to/file.txt';

        $record = [
            $sourceField => $sourceFilePath,
        ];

        $configuration = [
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'targetDirectoryPath' => 'some/path',
        ];

        $fileObject = $this->createMock(File::class);

        $subject = $this->createSubjectWithMockedGetFile();
        $subject->expects($this->once())
            ->method('getFile')
            ->with($configuration, $sourceFilePath)
            ->willReturn($fileObject);

        $this->assertTrue(
            $subject->process($configuration, $record)
        );

        $this->assertSame(
            $fileObject,
            $record[$targetField]
        );
    }

    public function testProcessGetsMultipleFiles(): void
    {
        $sourceField = 'sourceField';
        $targetField = 'targetField';
        $sourceFilePaths = 'file1.txt,file2.txt';

        $record = [
            $sourceField => $sourceFilePaths,
        ];

        $configuration = [
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'targetDirectoryPath' => 'some/path',
            'multipleRows' => true,
        ];

        $fileObject1 = $this->createMock(File::class);
        $fileObject2 = $this->createMock(File::class);

        $subject = $this->createSubjectWithMockedGetFile();
        $subject->expects($this->exactly(2))
            ->method('getFile')
            ->willReturnOnConsecutiveCalls($fileObject1, $fileObject2);

        $this->assertTrue(
            $subject->process($configuration, $record)
        );

        $this->assertSame(
            [$fileObject1, $fileObject2],
            $record[$targetField]
        );
    }

    public function testProcessWithSourcePath(): void
    {
        $sourceField = 'sourceField';
        $targetField = 'targetField';
        $sourcePath = 'prefix/';
        $sourceFilePath = 'file.txt';

        $record = [
            $sourceField => $sourceFilePath,
        ];

        $configuration = [
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'targetDirectoryPath' => 'some/path',
            'sourcePath' => $sourcePath,
        ];

        $fileObject = $this->createMock(File::class);

        $subject = $this->createSubjectWithMockedGetFile();
        $subject->expects($this->once())
            ->method('getFile')
            ->with($configuration, $sourcePath . $sourceFilePath)
            ->willReturn($fileObject);

        $this->assertTrue(
            $subject->process($configuration, $record)
        );

        $this->assertSame(
            $fileObject,
            $record[$targetField]
        );
    }

    public function testProcessWithCustomSeparator(): void
    {
        $sourceField = 'sourceField';
        $targetField = 'targetField';
        $separator = '|';
        $sourceFilePaths = 'file1.txt|file2.txt';

        $record = [
            $sourceField => $sourceFilePaths,
        ];

        $configuration = [
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'targetDirectoryPath' => 'some/path',
            'multipleRows' => true,
            'separator' => $separator,
        ];

        $fileObject1 = $this->createMock(File::class);
        $fileObject2 = $this->createMock(File::class);

        $subject = $this->createSubjectWithMockedGetFile();
        $subject->expects($this->exactly(2))
            ->method('getFile')
            ->willReturnOnConsecutiveCalls($fileObject1, $fileObject2);

        $this->assertTrue(
            $subject->process($configuration, $record)
        );

        $this->assertSame(
            [$fileObject1, $fileObject2],
            $record[$targetField]
        );
    }

    public function testIsConfigurationValidWithValidConfiguration(): void
    {
        $configuration = [
            'storageId' => 1,
            'targetDirectoryPath' => 'some/path',
            'sourceField' => 'source',
            'targetField' => 'target',
        ];

        $this->resourceStorage->expects($this->once())
            ->method('hasFolder')
            ->with($configuration['targetDirectoryPath'])
            ->willReturn(true);

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testIsConfigurationValidWithInvalidConfiguration(): void
    {
        $configuration = [
            'storageId' => 1,
            'targetDirectoryPath' => 'some/path',
            'sourceField' => 'source',
            // Missing targetField
        ];

        $this->subject->expects($this->once())
            ->method('logError')
            ->with(1_497_427_336);

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }
}
