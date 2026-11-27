<?php

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

use CPSIT\T3importExport\Resource\FileSourceGuard;
use CPSIT\T3importExport\Resource\FileTargetGuard;
use CPSIT\T3importExport\Resource\UrlSourceLoader;
use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Component\PreProcessor\GenerateUploadFile;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFileStructureTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateUploadFileTest
 */
class GenerateUploadFileTest extends TestCase
{
    protected string $sourceDirectory = '';

    use \CPSIT\T3importExport\Tests\Unit\Traits\AssertPropertyTrait;
    use MockFileStructureTrait;

    /**
     * @var GenerateUploadFile |MockObject
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

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets']);
        GeneralUtility::rmdir($this->sourceDirectory, true);
    }

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->sourceDirectory = Environment::getPublicPath() . '/typo3temp/var/tests/generate-file-' . uniqid('', true);
        GeneralUtility::mkdir_deep($this->sourceDirectory);
        $this->subject = $this->getMockBuilder(GenerateUploadFile::class)
            ->setMethods(['getAbsoluteFilePath'])->getMock();
        $this->subject->setFileSourceGuard(new FileSourceGuard([$this->sourceDirectory], []));

        $this->resourceStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration'])->getMock();

        $this->subject->withStorage($this->resourceStorage);

        $this->filePathFactory = $this->getMockBuilder(FilePathFactory::class)->setMethods(['createFromParts'])->getMock();
        $this->subject->injectFilePathFactory($this->filePathFactory);
        vfsStreamWrapper::register();
    }

    /**
     * Provides dependencies for injection tests
     */
    public function dependenciesDataProvider()
    {
        return [
            [StorageRepository::class, 'storageRepository']
        ];
    }

    /**
     * @test
     * @dataProvider dependenciesDataProvider
     * @param string $class Class name of the dependency to inject
     * @param string $propertyName The property holding the dependency
     */
    public function dependenciesCanBeInjected($class, $propertyName)
    {
        $mockDependency = $this->getMockBuilder($class)->disableOriginalConstructor()
            ->getMock();

        $methodName = 'inject' . ucfirst($propertyName);
        $this->subject->{$methodName}($mockDependency);

        $this->assertPropertySame(
            $mockDependency,
            $propertyName,
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getFileInitiallyReturnsEmptyString()
    {
        $sourceFilePath = $this->sourceDirectory . '/bang.txt';
        $storageConfiguration = [
            'basePath' => ''
        ];
        $configuration = [
            'targetDirectoryPath' => 'foo',
            'sourcePath' => $this->sourceDirectory . '/',
        ];

        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($storageConfiguration));

        $this->assertSame(
            '',
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }

    /**
     * @test
     */
    public function getFileCopiesFileToTarget()
    {
        [$rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure] = $this->mockFileStructure();

        vfsStream::setup($rootDirectory, null, $fileStructure);

        $storageConfiguration = [
            'basePath' => $rootDirectory
        ];


        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($storageConfiguration));

        $expectedFilePath = $storageConfiguration['basePath'] . DIRECTORY_SEPARATOR . $configuration['targetDirectoryPath'] . DIRECTORY_SEPARATOR . $sourceFileName;

        $this->filePathFactory->expects($this->once())
            ->method('createFromParts')
            ->with([$storageConfiguration['basePath'], $configuration['targetDirectoryPath']])
            ->will($this->returnValue($storageConfiguration['basePath'] . DIRECTORY_SEPARATOR . $configuration['targetDirectoryPath'] . DIRECTORY_SEPARATOR));

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($expectedFilePath)
            ->will($this->returnValue(vfsStream::url($expectedFilePath)));

        $this->assertSame(
            $expectedFilePath,
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }

    /**
     * @test
     */
    public function getFileDoesNotCopyFilesWithDeniedNames(): void
    {
        vfsStream::setup('root', null, [
            'sourceDir' => ['foo.php' => '<?php'],
            'targetDir' => [],
        ]);
        $this->resourceStorage->method('getConfiguration')
            ->willReturn(['basePath' => vfsStream::url('root')]);
        $this->filePathFactory->method('createFromParts')
            ->willReturn(vfsStream::url('root') . '/targetDir/');
        $this->subject->method('getAbsoluteFilePath')->willReturnArgument(0);

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], vfsStream::url('root') . '/sourceDir/foo.php');
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED, $exception->getCode());
        }
        self::assertFileDoesNotExist(vfsStream::url('root') . '/targetDir/foo.php');
    }

    protected function prepareTargetDirectory(): void
    {
        vfsStreamWrapper::register();
        vfsStream::setup('root', null, ['targetDir' => []]);
        $this->resourceStorage->method('getConfiguration')
            ->willReturn(['basePath' => vfsStream::url('root')]);
        $this->filePathFactory->method('createFromParts')
            ->willReturn(vfsStream::url('root') . '/targetDir/');
        $this->subject->method('getAbsoluteFilePath')->willReturnArgument(0);
    }

    /**
     * @param array<string, Response> $responses by URL
     */
    protected function useHttpResponses(array $responses, FileSourceGuard $guard): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(
            static fn(string $url): Response => $responses[$url] ?? new Response('php://temp', 404)
        );
        $this->subject->setFileSourceGuard($guard);
        $this->subject->setUrlSourceLoader(new UrlSourceLoader($requestFactory, $guard));
    }

    protected static function response(string $body, int $status = 200, array $headers = []): Response
    {
        $response = new Response('php://temp', $status, $headers);
        $response->getBody()->write($body);
        $response->getBody()->rewind();

        return $response;
    }

    public static function sourceOutsideOfAllowedDirectoriesDataProvider(): iterable
    {
        yield 'system file' => ['/etc/hosts.txt', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'project file' => [Environment::getProjectPath() . '/composer.json', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'file in allowed directory without source path' => ['{source}/image.txt', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'dot dot segment' => ['{source}/../secret.txt', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'php filter' => ['php://filter/read=convert.base64-encode/resource={source}/../secret.txt', FileSourceGuard::ERROR_URL_NOT_ALLOWED];
        yield 'compress.zlib' => ['compress.zlib://{source}/../secret.txt', FileSourceGuard::ERROR_URL_NOT_ALLOWED];
        yield 'file' => ['file:///etc/hosts.txt', FileSourceGuard::ERROR_URL_NOT_ALLOWED];
        yield 'data' => ['data:text/plain;base64,c2VjcmV0#/secret.txt', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
    }

    /**
     * @test
     * @dataProvider sourceOutsideOfAllowedDirectoriesDataProvider
     */
    public function getFileDoesNotCopySourceOutsideOfAllowedDirectories(string $source, int $code): void
    {
        $this->prepareTargetDirectory();
        file_put_contents(\dirname($this->sourceDirectory) . '/secret.txt', 'secret');
        file_put_contents($this->sourceDirectory . '/image.txt', 'image');
        $source = str_replace('{source}', $this->sourceDirectory, $source);

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], $source);
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($code, $exception->getCode());
        } finally {
            @unlink(\dirname($this->sourceDirectory) . '/secret.txt');
        }
        self::assertSame([], array_values(array_diff(scandir(vfsStream::url('root') . '/targetDir'), ['.', '..'])));
    }

    /**
     * @test
     */
    public function getFileDoesNotLoadUrlOfOtherHost(): void
    {
        $this->prepareTargetDirectory();
        $this->useHttpResponses(
            ['http://127.0.0.1:18081/secret.txt' => self::response('secret')],
            new FileSourceGuard([$this->sourceDirectory], ['data.example.org'])
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'http://127.0.0.1:18081/secret.txt');
    }

    /**
     * @test
     */
    public function getFileDoesNotLoadUrlsWithoutAllowedHosts(): void
    {
        $this->prepareTargetDirectory();
        $this->subject->setFileSourceGuard(new FileSourceGuard([$this->sourceDirectory]));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'https://data.example.org/image.jpg');
    }

    /**
     * @test
     */
    public function getFileDoesNotFollowRedirectToOtherHost(): void
    {
        $this->prepareTargetDirectory();
        $this->useHttpResponses(
            [
                'https://data.example.org/secret.txt' => self::response('', 302, ['Location' => 'http://127.0.0.1:18081/secret.txt']),
                'http://127.0.0.1:18081/secret.txt' => self::response('secret'),
            ],
            new FileSourceGuard([$this->sourceDirectory], ['data.example.org'])
        );

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'https://data.example.org/secret.txt');
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(FileSourceGuard::ERROR_HOST_NOT_ALLOWED, $exception->getCode());
        }
        self::assertFileDoesNotExist(vfsStream::url('root') . '/targetDir/secret.txt');
    }

    /**
     * @test
     */
    public function getFileLoadsUrlOfAllowedHost(): void
    {
        $this->prepareTargetDirectory();
        $this->useHttpResponses(
            [
                'https://data.example.org/old.txt' => self::response('', 301, ['Location' => '/image.txt']),
                'https://data.example.org/image.txt' => self::response('image'),
            ],
            new FileSourceGuard([$this->sourceDirectory], ['data.example.org'])
        );

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'https://data.example.org/old.txt');

        self::assertSame(vfsStream::url('root') . '/targetDir/old.txt', $result);
        self::assertSame('image', file_get_contents($result));
        self::assertSame(['old.txt'], array_values(array_diff(scandir(vfsStream::url('root') . '/targetDir'), ['.', '..'])));
    }

    /**
     * @test
     */
    public function getFileDoesNotChangeTargetIfUrlCannotBeLoaded(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents(vfsStream::url('root') . '/targetDir/image.txt', 'old');
        $this->useHttpResponses([], new FileSourceGuard([$this->sourceDirectory], ['data.example.org']));

        self::assertSame('', $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'https://data.example.org/image.txt'));
        self::assertSame('old', file_get_contents(vfsStream::url('root') . '/targetDir/image.txt'));
        self::assertSame(['image.txt'], array_values(array_diff(scandir(vfsStream::url('root') . '/targetDir'), ['.', '..'])));
    }

    /**
     * @test
     */
    public function getFileSanitizesSvgFiles(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents(
            $this->sourceDirectory . '/image.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>'
        );

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/image.svg');

        self::assertStringNotContainsString('script', (string)file_get_contents($result));
        self::assertStringContainsString('rect', (string)file_get_contents($result));
    }

    public static function hostChangingValueDataProvider(): iterable
    {
        yield 'user information' => ['https://data.example.org', '@127.0.0.1:18081/secret.txt'];
        yield 'user information of allowed host' => ['https://data.example.org', '@cdn.example.org/secret.txt'];
        yield 'host suffix' => ['https://data', '.example.org/secret.txt'];
    }

    /**
     * @test
     * @dataProvider hostChangingValueDataProvider
     */
    public function processDoesNotLoadUrlWhoseHostIsChangedByTheValue(string $sourcePath, string $value): void
    {
        $this->subject->setFileSourceGuard(new FileSourceGuard([$this->sourceDirectory], ['data.example.org', 'cdn.example.org']));
        $record = ['source' => $value];
        $this->expectException(InvalidArgumentException::class);

        $this->subject->process(
            [
                'sourceField' => 'source',
                'targetField' => 'target',
                'targetDirectoryPath' => 'targetDir',
                'sourcePath' => $sourcePath,
                'multipleRows' => false,
            ],
            $record
        );
    }

    /**
     * @test
     */
    public function processCopiesFileOfLocalSourcePath(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/image.txt', 'image');
        $record = ['source' => 'image.txt'];

        $this->subject->process(
            [
                'sourceField' => 'source',
                'targetField' => 'target',
                'targetDirectoryPath' => 'targetDir',
                'sourcePath' => $this->sourceDirectory . '/',
                'multipleRows' => false,
            ],
            $record
        );

        self::assertSame(vfsStream::url('root') . '/targetDir/image.txt', $record['target']);
        self::assertSame('image', file_get_contents($record['target']));
    }

    public static function valueOutsideOfSourcePathDataProvider(): iterable
    {
        yield 'system file' => ['/etc/', '/etc/hosts.txt', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'sibling of existing directory without trailing slash' => ['{source}/a', 'b/secret.txt', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'parent of existing directory without trailing slash' => ['{source}/a', '.txt', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'URL source path' => ['https://data.example.org/', '{source}/a/image.txt', FileSourceGuard::ERROR_HOST_NOT_ALLOWED];
    }

    /**
     * @test
     * @dataProvider valueOutsideOfSourcePathDataProvider
     */
    public function processDoesNotCopyFileOutsideOfSourceDirectory(string $sourcePath, string $value, int $code): void
    {
        $this->prepareTargetDirectory();
        GeneralUtility::mkdir_deep($this->sourceDirectory . '/a');
        GeneralUtility::mkdir_deep($this->sourceDirectory . '/ab');
        file_put_contents($this->sourceDirectory . '/a/image.txt', 'image');
        file_put_contents($this->sourceDirectory . '/ab/secret.txt', 'secret');
        file_put_contents($this->sourceDirectory . '/a.txt', 'secret');
        $record = ['source' => str_replace('{source}', $this->sourceDirectory, $value)];

        try {
            $this->subject->process(
                [
                    'sourceField' => 'source',
                    'targetField' => 'target',
                    'targetDirectoryPath' => 'targetDir',
                    'sourcePath' => str_replace('{source}', $this->sourceDirectory, $sourcePath),
                    'multipleRows' => false,
                ],
                $record
            );
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($code, $exception->getCode());
        }
        self::assertSame([], $this->targetFiles());
    }

    /**
     * @test
     */
    public function processCopiesFileBelowExistingSourceDirectoryWithoutTrailingSlash(): void
    {
        $this->prepareTargetDirectory();
        GeneralUtility::mkdir_deep($this->sourceDirectory . '/a');
        file_put_contents($this->sourceDirectory . '/a/image.txt', 'image');
        $record = ['source' => '/image.txt'];

        $this->subject->process(
            [
                'sourceField' => 'source',
                'targetField' => 'target',
                'targetDirectoryPath' => 'targetDir',
                'sourcePath' => $this->sourceDirectory . '/a',
                'multipleRows' => false,
            ],
            $record
        );

        self::assertSame('image', file_get_contents($record['target']));
    }

    protected const SVG = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';

    protected function targetFiles(): array
    {
        return array_values(array_diff(scandir(vfsStream::url('root') . '/targetDir'), ['.', '..']));
    }

    public static function urlWithQueryOrFragmentDataProvider(): iterable
    {
        yield 'fragment' => ['https://data.example.org/evil.svg#a'];
        yield 'query' => ['https://data.example.org/evil.svg?x=1'];
    }

    /**
     * @test
     * @dataProvider urlWithQueryOrFragmentDataProvider
     */
    public function getFileUsesPathOfUrlAsFileNameAndSanitizesSvg(string $url): void
    {
        $this->prepareTargetDirectory();
        $this->useHttpResponses([$url => self::response(self::SVG)], new FileSourceGuard([$this->sourceDirectory], ['data.example.org']));

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], $url);

        self::assertSame(vfsStream::url('root') . '/targetDir/evil.svg', $result);
        self::assertStringNotContainsString('script', (string)file_get_contents($result));
        self::assertSame(['evil.svg'], $this->targetFiles());
    }

    /**
     * @test
     */
    public function getFileSanitizesSvgContentWithOtherFileExtension(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/image.txt', '<?xml version="1.0"?>' . "\n" . self::SVG);

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/image.txt');

        self::assertSame(vfsStream::url('root') . '/targetDir/image.txt', $result);
        self::assertStringNotContainsString('script', (string)file_get_contents($result));
        self::assertStringContainsString('rect', (string)file_get_contents($result));
    }

    public static function svgContentDataProvider(): iterable
    {
        yield 'leading whitespace' => ['image.xml', "\n  \n" . self::SVG];
        yield 'byte order mark' => ['image.jpg', "\xEF\xBB\xBF" . self::SVG];
        yield 'xml declaration and long comment' => ['image.jpg', '<?xml version="1.0"?>' . "\n<!--" . str_repeat('x', 20000) . "-->\n" . self::SVG];
        yield 'processing instruction' => ['image.txt', '<?xml version="1.0"?><?xml-stylesheet href="a.css"?>' . self::SVG];
        yield 'document type' => ['image.txt', '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . self::SVG];
        yield 'namespace prefix' => ['image.txt', '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:script>alert(1)</s:script><s:rect width="1" height="1"/></s:svg>'];
        yield 'document type with internal subset' => ['image.txt', '<!DOCTYPE svg [<!ENTITY a "b">]>' . self::SVG];
        yield 'invalid xml' => ['image.txt', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/><b></svg>'];
    }

    /**
     * @test
     * @dataProvider svgContentDataProvider
     */
    public function getFileSanitizesSvgContentByRootElement(string $fileName, string $content): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, $content);

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);

        self::assertStringNotContainsString('alert', (string)file_get_contents($result));
    }

    /**
     * The SVG sanitizer of TYPO3 12 refuses an `SVG` root element in upper
     * case: the file is not written.
     *
     * @test
     */
    public function getFileDoesNotWriteSvgContentWhichTheSanitizerRefuses(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/image.txt', '<SVG xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></SVG>');

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/image.txt');
            self::fail('No exception thrown');
        } catch (\LogicException $exception) {
        }
        self::assertSame([], $this->targetFiles());
    }

    public static function otherMarkupDataProvider(): iterable
    {
        yield 'html with inline svg' => ['page.txt', '<html><body><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg></body></html>'];
        yield 'rss with inline svg' => ['feed.xml', '<?xml version="1.0"?><rss version="2.0"><channel><description><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg></description></channel></rss>'];
        yield 'text mentioning svg' => ['notes.txt', 'An <svg> element can contain <script>alert(1)</script>.'];
    }

    /**
     * @test
     * @dataProvider otherMarkupDataProvider
     */
    public function getFileDoesNotSanitizeOtherMarkup(string $fileName, string $content): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, $content);

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);

        self::assertSame($content, file_get_contents($result));
    }

    /**
     * @test
     */
    public function getFileLogsWarningIfSanitizingEmptiesTheFile(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/image.svg', 'no svg');

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/image.svg');

        self::assertSame('', file_get_contents($result));
        self::assertTrue($this->subject->hasMessageWithId(GenerateUploadFile::WARNING_SVG_EMPTY));
    }

    public static function activeContentFileNameDataProvider(): iterable
    {
        yield 'html' => ['page.html'];
        yield 'htm' => ['page.htm'];
        yield 'xhtml' => ['page.xhtml'];
        yield 'xht' => ['page.xht'];
        yield 'js' => ['script.js'];
        yield 'mjs' => ['module.mjs'];
        yield 'upper case' => ['PAGE.HTML'];
        yield 'last of two extensions' => ['image.jpg.html'];
        yield 'first of two extensions' => ['page.html.zzz'];
        yield 'middle extension' => ['script.js.min.txt'];
    }

    public static function deniedActiveContentFileNameDataProvider(): iterable
    {
        yield from self::activeContentFileNameDataProvider();
        // also in the default fileDenyPattern of TYPO3
        yield 'shtml' => ['page.shtml'];
        yield 'shtm' => ['page.shtm'];
    }

    /**
     * @test
     * @dataProvider deniedActiveContentFileNameDataProvider
     */
    public function getFileDoesNotCopyActiveContentByDefault(string $fileName): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, '<script>alert(1)</script>');

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED, $exception->getCode());
        }
        self::assertSame([], $this->targetFiles());
    }

    /**
     * @test
     */
    public function getFileDoesNotLoadUrlWithActiveContentName(): void
    {
        $this->prepareTargetDirectory();
        $this->useHttpResponses(
            ['https://data.example.org/page.html?x=1' => self::response('<script>alert(1)</script>')],
            new FileSourceGuard([$this->sourceDirectory], ['data.example.org'])
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'https://data.example.org/page.html?x=1');
    }

    /**
     * @test
     * @dataProvider activeContentFileNameDataProvider
     */
    public function getFileCopiesActiveContentIfAllowed(string $fileName): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets'] = true;
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, '<script>alert(1)</script>');

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);

        self::assertSame('<script>alert(1)</script>', file_get_contents($result));
    }

    public static function nonBooleanSettingDataProvider(): iterable
    {
        yield 'string yes' => ['yes'];
        yield 'string 1' => ['1'];
        yield 'integer 1' => [1];
        yield 'string true' => ['true'];
    }

    /**
     * @test
     * @dataProvider nonBooleanSettingDataProvider
     */
    public function getFileAllowsActiveContentOnlyWithTrue(mixed $value): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets'] = $value;
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/page.html', '<script>alert(1)</script>');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/page.html');
    }

    public static function invalidLastExtensionDataProvider(): iterable
    {
        yield 'parameter' => ['page.html;a=b'];
        yield 'encoded dot' => ['page%2ehtml'];
        yield 'no extension' => ['README'];
        yield 'dash' => ['image.jp-g'];
        yield 'underscore' => ['image.jp_g'];
        yield 'percent' => ['image.%6Apg'];
    }

    /**
     * @test
     * @dataProvider invalidLastExtensionDataProvider
     */
    public function getFileDoesNotCopyFilesWithInvalidLastExtension(string $fileName): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets'] = true;
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, 'content');

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED, $exception->getCode());
        }
        self::assertSame([], $this->targetFiles());
    }

    public static function allowedFileNameDataProvider(): iterable
    {
        yield 'xml' => ['feed.xml'];
        yield 'html in base name' => ['html-export.txt'];
        yield 'json' => ['data.JSON'];
        yield 'digits' => ['archive.7z'];
    }

    /**
     * @test
     * @dataProvider allowedFileNameDataProvider
     */
    public function getFileCopiesOtherFileTypes(string $fileName): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, 'content');

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);

        self::assertSame('content', file_get_contents($result));
    }

    public static function xmlWithActiveContentDataProvider(): iterable
    {
        yield 'xhtml element' => ['<?xml version="1.0"?><feed><content><div xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script></div></content></feed>'];
        yield 'prefixed xhtml element' => ['<feed xmlns:h="http://www.w3.org/1999/xhtml"><h:script>alert(1)</h:script></feed>'];
        yield 'svg element' => ['<feed><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg></feed>'];
        yield 'xml stylesheet' => ['<?xml version="1.0"?><?xml-stylesheet type="text/xsl" href="a.xsl"?><feed/>'];
    }

    /**
     * @test
     * @dataProvider xmlWithActiveContentDataProvider
     */
    public function getFileLogsWarningForXmlWithActiveContent(string $content): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/feed.xml', $content);

        $result = $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/feed.xml');

        self::assertSame($content, file_get_contents($result));
        self::assertTrue($this->subject->hasMessageWithId(GenerateUploadFile::WARNING_XML_ACTIVE_CONTENT));
    }

    /**
     * @test
     */
    public function getFileDoesNotLogWarningForPlainXml(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/feed.xml', '<?xml version="1.0"?><feed><title>svg and script</title></feed>');

        $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/feed.xml');

        self::assertFalse($this->subject->hasMessageWithId(GenerateUploadFile::WARNING_XML_ACTIVE_CONTENT));
    }

    public static function deniedTargetNameDataProvider(): iterable
    {
        yield 'compressed svg' => ['image.svgz'];
        yield 'compressed svg in upper case' => ['image.SVGZ'];
        yield 'question mark' => ['evil.svg?x=1'];
        yield 'hash' => ['evil.svg#a'];
    }

    /**
     * @test
     * @dataProvider deniedTargetNameDataProvider
     */
    public function getFileDoesNotCopyFilesWithDeniedTargetNames(string $fileName): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/' . $fileName, (string)gzencode(self::SVG));

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/' . $fileName);
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED, $exception->getCode());
        }
        self::assertSame([], $this->targetFiles());
    }

    /**
     * @test
     */
    public function getFileRemovesTemporaryFileIfSanitizingFails(): void
    {
        $this->prepareTargetDirectory();
        file_put_contents($this->sourceDirectory . '/image.svg', self::SVG);
        $sanitizer = $this->getMockBuilder(SvgSanitizer::class)->onlyMethods(['sanitizeFile'])->getMock();
        $sanitizer->method('sanitizeFile')->willThrowException(new \RuntimeException('failed', 1795400298));
        GeneralUtility::addInstance(SvgSanitizer::class, $sanitizer);

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir', 'sourcePath' => $this->sourceDirectory . '/'], $this->sourceDirectory . '/image.svg');
            self::fail('No exception thrown');
        } catch (\RuntimeException $exception) {
            self::assertSame(1795400298, $exception->getCode());
        } finally {
            GeneralUtility::purgeInstances();
        }
        self::assertSame([], $this->targetFiles());
    }

    /**
     * @test
     */
    public function processDoesNotCopyExtensionPathOfRecord(): void
    {
        $this->prepareTargetDirectory();
        $record = ['source' => 'EXT:core/composer.json'];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        $this->subject->process(
            [
                'sourceField' => 'source',
                'targetField' => 'target',
                'targetDirectoryPath' => 'targetDir',
                'multipleRows' => false,
            ],
            $record
        );
    }

    /**
     * @test
     */
    public function getFileDoesNotFollowRedirectToOtherPort(): void
    {
        $this->prepareTargetDirectory();
        $this->useHttpResponses(
            [
                'http://127.0.0.1:18081/image.txt' => self::response('', 302, ['Location' => 'http://127.0.0.1:18082/secret.txt']),
                'http://127.0.0.1:18082/secret.txt' => self::response('secret'),
            ],
            new FileSourceGuard([$this->sourceDirectory], ['127.0.0.1:18081'])
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], 'http://127.0.0.1:18081/image.txt');
    }
}
