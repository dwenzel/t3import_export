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

use CPSIT\T3importExport\Resource\FileTargetGuard;
use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Component\PreProcessor\GenerateUploadFile;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFileStructureTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateUploadFileTest
 */
class GenerateUploadFileTest extends TestCase
{
    use \CPSIT\T3importExport\Tests\Unit\Traits\AssertPropertyTrait;
    use MockFileStructureTrait;

    /**
     * @var GenerateUploadFile |MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage|MockObject
     */
    protected $resourceStorage;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository|MockObject
     */
    protected $storageRepository;

    /**
     * @var FilePathFactory|MockObject
     */
    protected $filePathFactory;

    /**
     * set up subject
     */
    public function setUp(): void
    {
        $this->subject = $this->getMockBuilder(GenerateUploadFile::class)
            ->setMethods(['getAbsoluteFilePath'])->getMock();

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
        $sourceFilePath = 'bang';
        $storageConfiguration = [
            'basePath' => ''
        ];
        $configuration = [
            'targetDirectoryPath' => 'foo'
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
        list($rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure) = $this->mockFileStructure();

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
}
