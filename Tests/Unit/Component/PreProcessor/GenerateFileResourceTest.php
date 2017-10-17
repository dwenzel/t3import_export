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

use CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource;
use CPSIT\T3importExport\Factory\FilePathFactory;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateFileResourceTest
 */
class GenerateFileResourceTest extends UnitTestCase
{
    /**
     * @var GenerateFileResource |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceStorage;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storageRepository;

    /**
     * @var FilePathFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filePathFactory;

    /**
     * @var FileIndexRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileIndexRepository;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(GenerateFileResource::class)
            ->setMethods(['logError', 'getAbsoluteFilePath'])->getMock();

        $this->resourceStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()
            ->setMethods(['hasFile', 'getFile', 'getConfiguration'])->getMock();
        $this->inject(
            $this->subject,
            'resourceStorage',
            $this->resourceStorage
        );
        $this->fileIndexRepository = $this->getMockBuilder(FileIndexRepository::class)->disableOriginalConstructor()
            ->setMethods(['add'])->getMock();
        $this->subject->injectFileIndexRepository($this->fileIndexRepository);

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
            [StorageRepository::class, 'storageRepository'],
            [FileIndexRepository::class, 'fileIndexRepository']
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

        $this->assertAttributeSame(
            $mockDependency,
            $propertyName,
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getFileReturnsExistingFileFromResourceStorage()
    {
        $mockFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();

        $targetDirectoryPath = 'foo/';
        $fileName = 'bar.x';
        $filePath = 'sourcePath/' . $fileName;
        $expectedPath = $targetDirectoryPath . $fileName;

        $configuration = [
            'targetDirectoryPath' => $targetDirectoryPath
        ];

        $this->resourceStorage->expects($this->once())
            ->method('hasFile')
            ->with($expectedPath)
            ->will($this->returnValue(true));

        $this->resourceStorage->expects($this->once())
            ->method('getFile')
            ->with($expectedPath)
            ->will($this->returnValue($mockFile));

        $this->assertSame(
            $mockFile,
            $this->subject->getFile($configuration, $filePath)
        );
    }

    /**
     * @test
     */
    public function getFileCopiesFileToTarget()
    {
        $mockFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();

        $rootDirectory = 'root';

        $sourceFileContent = 'source file content';

        $sourceDirectory = 'sourceDir';
        $sourceFileName = 'foo.csv';
        $sourceFilePath = 'vfs://' . $rootDirectory . DIRECTORY_SEPARATOR . $sourceDirectory . DIRECTORY_SEPARATOR . $sourceFileName;
        $targetDirectory = 'targetDir';
        $configuration = [
            'targetDirectoryPath' => $targetDirectory
        ];

        $fileStructure = [
            $sourceDirectory => [
                $sourceFileName => $sourceFileContent
            ],
            $targetDirectory => []
        ];

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
        $this->resourceStorage->expects($this->once())->method('getFile')
            ->with($targetDirectory . DIRECTORY_SEPARATOR . $sourceFileName)
            ->will($this->returnValue($mockFile));

        $this->assertSame(
            $mockFile,
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }

    /**
     * @test
     */
    public function getFileReturnsNullOnFailure()
    {
        $rootDirectory = 'root';

        $sourceFileContent = 'source file content';

        $sourceDirectory = 'sourceDir';
        $sourceFileName = 'foo.csv';
        $sourceFilePath = 'vfs://' . $rootDirectory . DIRECTORY_SEPARATOR . $sourceDirectory . DIRECTORY_SEPARATOR . $sourceFileName;
        $targetDirectory = 'invalidDirectory';

        $configuration = [
            'targetDirectoryPath' => $targetDirectory
        ];

        $fileStructure = [
            $sourceDirectory => [
                $sourceFileName => $sourceFileContent
            ]
        ];

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

        $this->assertNull(
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }
}
