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

use CPSIT\T3importExport\Component\PreProcessor\GenerateUploadFile;
use CPSIT\T3importExport\Factory\FilePathFactory;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
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
            ->onlyMethods(['getAbsoluteFilePath'])->getMock();

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
        $nonExistentSourceFilePath = vfsStream::url($rootDirectory) . '/nonexistent/file.txt';
        
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
        
        // Mock getAbsoluteFilePath to return the same path for file operations
        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
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
        
        // Mock getAbsoluteFilePath to return the same path for file operations
        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($expectedTargetPath)
            ->willReturn($expectedTargetPath);
            
        $result = $this->subject->getFile($configuration, $sourceFilePath);
        
        // Assert that the method returns the expected path
        $this->assertSame($expectedTargetPath, $result);
        
        // Assert that the file was actually copied
        $this->assertFileExists($expectedTargetPath);
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
        $sourceFilePath = 'vfs://' . $rootDirectory . DIRECTORY_SEPARATOR . $sourceDirectory . DIRECTORY_SEPARATOR . $sourceFileName;
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
