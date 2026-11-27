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
use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFileIndexRepositoryTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFilePathFactoryTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFileStructureTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockResourceStorageTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\File;

/**
 * Class GenerateFileResourceTest
 */
class GenerateFileResourceTest extends TestCase
{
    protected string $sourceDirectory = '';

    use MockFileIndexRepositoryTrait,
        MockFilePathFactoryTrait,
        MockFileStructureTrait,
        MockMessageContainerTrait,
        MockResourceStorageTrait;

    /**
     * @var GenerateFileResource |MockObject
     */
    protected $subject;

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->sourceDirectory, true);
    }

    /**
     * set up subject
     * @throws vfsStreamException
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp(): void
    {
        $this->sourceDirectory = Environment::getPublicPath() . '/typo3temp/var/tests/generate-file-' . uniqid('', true);
        GeneralUtility::mkdir_deep($this->sourceDirectory);
        $this->mockFileIndexRepository()
            ->mockResourceStorage()
            ->mockFilePathFactory()
            ->mockMessageContainer();

        $this->subject = $this->getMockBuilder(GenerateFileResource::class)
            ->setConstructorArgs(
                [
                    $this->fileIndexRepository,
                    $this->resourceStorage,
                    $this->filePathFactory,
                    $this->messageContainer
                ]
            )
            ->setMethods(['logError', 'getAbsoluteFilePath'])->getMock();
        $this->subject->setFileSourceGuard(new FileSourceGuard([$this->sourceDirectory], []));

        vfsStreamWrapper::register();
    }


    public function testGetFileReturnsExistingFileFromResourceStorage(): void
    {
        $mockFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        file_put_contents($this->sourceDirectory . '/bar.x', 'bar');

        $targetDirectoryPath = 'foo/';
        $fileName = 'bar.x';
        $filePath = $this->sourceDirectory . '/' . $fileName;
        $expectedPath = $targetDirectoryPath . $fileName;

        $configuration = [
            'targetDirectoryPath' => $targetDirectoryPath,
            'sourcePath' => $this->sourceDirectory . '/',
        ];
        $this->mockTargetDirectory();

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

    protected function mockTargetDirectory(): void
    {
        $this->resourceStorage->method('getConfiguration')
            ->willReturn(['basePath' => 'fileadmin/']);
        $this->filePathFactory->method('createFromParts')
            ->willReturn('fileadmin/targetDir/');
        $this->subject->method('getAbsoluteFilePath')->willReturnArgument(0);
    }

    public static function existingFileOfDeniedSourceDataProvider(): iterable
    {
        yield 'local file without source path' => [null, '{source}/image.jpg', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'local file outside of source directory' => ['{source}/a', '{source}/ab/image.jpg', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'file outside of allowed directories' => ['/etc/', '/etc/image.jpg', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'URL of other host' => [null, 'https://example.org/image.jpg', FileSourceGuard::ERROR_HOST_NOT_ALLOWED];
        yield 'other scheme' => [null, 'ftp://example.org/image.jpg', FileSourceGuard::ERROR_URL_NOT_ALLOWED];
        yield 'denied target name' => ['{source}/', '{source}/image.php', FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED];
        yield 'active content' => ['{source}/', '{source}/page.html', FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED];
        yield 'active content of URL' => [null, 'https://example.org/page.html.zzz', FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED];
        yield 'invalid last extension' => ['{source}/', '{source}/page.html;a=b', FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED];
    }

    /**
     * @test
     * @dataProvider existingFileOfDeniedSourceDataProvider
     */
    public function getFileChecksSourceAndNameBeforeUsingExistingFile(?string $sourcePath, string $value, int $code): void
    {
        $configuration = ['targetDirectoryPath' => 'targetDir'];
        if ($sourcePath !== null) {
            $configuration['sourcePath'] = str_replace('{source}', $this->sourceDirectory, $sourcePath);
        }
        GeneralUtility::mkdir_deep($this->sourceDirectory . '/a');
        $this->mockTargetDirectory();
        $this->resourceStorage->method('hasFile')->willReturn(true);
        $this->resourceStorage->expects($this->never())->method('getFile');

        try {
            $this->subject->getFile($configuration, str_replace('{source}', $this->sourceDirectory, $value));
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($code, $exception->getCode());
        }
    }

    public function testGetFileCopiesFileToTarget(): void
    {
        $mockFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();

        list($rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure) = $this->mockFileStructure();

        $this->assertFileGeneratedAccordingToConfiguration($rootDirectory, $fileStructure, $configuration['targetDirectoryPath'], $sourceFileName);

        $this->resourceStorage->expects($this->once())->method('getFile')
            ->with(...[$targetDirectory . DIRECTORY_SEPARATOR . $sourceFileName])
            ->willReturn($mockFile);

        $this->assertSame(
            $mockFile,
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }

    public function testGetFileReturnsNullOnFailure(): void
    {
        $rootDirectory = 'root';

        $sourceFileContent = 'source file content';

        $sourceDirectory = 'sourceDir';
        $sourceFileName = 'foo.csv';
        $sourceFilePath = $this->sourceDirectory . '/' . $sourceFileName;
        $targetDirectory = 'invalidDirectory';

        $configuration = [
            'targetDirectoryPath' => $targetDirectory,
            'sourcePath' => $this->sourceDirectory . '/',
        ];

        $fileStructure = [
            $sourceDirectory => [
                $sourceFileName => $sourceFileContent
            ]
        ];

        $this->assertFileGeneratedAccordingToConfiguration($rootDirectory, $fileStructure, $configuration['targetDirectoryPath'], $sourceFileName);

        $this->assertNull(
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }

    /**
     * @param string $rootDirectory
     * @param array $fileStructure
     * @param $targetDirectoryPath
     * @param string $sourceFileName
     */
    protected function assertFileGeneratedAccordingToConfiguration(string $rootDirectory, array $fileStructure, $targetDirectoryPath, string $sourceFileName): void
    {
        vfsStream::setup($rootDirectory, null, $fileStructure);

        $storageConfiguration = [
            'basePath' => $rootDirectory
        ];


        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($storageConfiguration);

        $expectedFilePath = $storageConfiguration['basePath'] . DIRECTORY_SEPARATOR . $targetDirectoryPath . DIRECTORY_SEPARATOR . $sourceFileName;

        $this->filePathFactory->expects($this->once())
            ->method('createFromParts')
            ->with([$storageConfiguration['basePath'], $targetDirectoryPath])
            ->willReturn($storageConfiguration['basePath'] . DIRECTORY_SEPARATOR . $targetDirectoryPath . DIRECTORY_SEPARATOR);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$expectedFilePath])
            ->willReturn(vfsStream::url($expectedFilePath));
    }

    /**
     * @test
     */
    public function getFileDoesNotCopyFilesWithDeniedNames(): void
    {
        vfsStreamWrapper::register();
        vfsStream::setup('root', null, [
            'sourceDir' => ['foo.php' => '<?php'],
            'targetDir' => [],
        ]);
        $this->resourceStorage->method('hasFile')->willReturn(false);
        $this->resourceStorage->method('getConfiguration')
            ->willReturn(['basePath' => vfsStream::url('root')]);
        $this->filePathFactory->method('createFromParts')
            ->willReturn(vfsStream::url('root') . '/targetDir/');
        $this->subject->method('getAbsoluteFilePath')->willReturnArgument(0);
        $this->fileIndexRepository->expects($this->never())->method('add');

        try {
            $this->subject->getFile(['targetDirectoryPath' => 'targetDir'], vfsStream::url('root') . '/sourceDir/foo.php');
            self::fail('No exception thrown');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED, $exception->getCode());
        }
        self::assertFileDoesNotExist(vfsStream::url('root') . '/targetDir/foo.php');
    }
}
