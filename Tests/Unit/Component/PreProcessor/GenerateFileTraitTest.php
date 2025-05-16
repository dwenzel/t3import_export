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

use CPSIT\T3importExport\Component\PreProcessor\GenerateFileTrait;
use CPSIT\T3importExport\Factory\FilePathFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

// Class that uses the GenerateFileTrait for testing
class GenerateFileTraitImplementation
{
    use GenerateFileTrait;

    protected $filePathFactory;

    public function getFile($configuration, $sourceFilePath)
    {
        return '';
    }
}

/**
 * Class GenerateFileTraitTest
 */
class GenerateFileTraitTest extends TestCase
{
    protected $subject;
    protected $storage;
    protected $storageRepository;
    protected $filePathFactory;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(GenerateFileTraitImplementation::class)
            ->onlyMethods(['logError', 'getFile'])
            ->getMock();

        $this->filePathFactory = $this->createMock(FilePathFactory::class);
        $this->subject->injectFilePathFactory($this->filePathFactory);

        $this->storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByUid'])
            ->getMock();

        $this->subject->injectStorageRepository($this->storageRepository);
    }

    /**
     * Provides dependencies for injection tests
     */
    public static function dependenciesDataProvider()
    {
        return [
            [FilePathFactory::class, 'filePathFactory']
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

    public static function invalidConfigurationDataProvider()
    {
        // $configuration, $expected, $errorId
        return [
            // empty configuration
            [
                [],
                false,
                1_499_007_587,
                null
            ],
            // missing target directory path
            [
                [
                    'foo' => 'bar'
                ],
                false,
                1_497_427_320,
                null
            ],
            // missing source field name
            [
                [
                    'targetDirectoryPath' => 'bar'
                ],
                false,
                1_497_427_335,
                null
            ],
            // missing target field name
            [
                [
                    'targetDirectoryPath' => 'bar',
                    'sourceField' => 'baz'
                ],
                false,
                1_497_427_336,
                null
            ],
            // missing storage id
            [
                [
                    'targetDirectoryPath' => 'bar',
                    'sourceField' => 'baz',
                    'targetField' => 'baz'
                ],
                false,
                1_497_427_302,
                null
            ],
            // missing resourceStorage
            [
                [
                    'storageId' => 42,
                    'targetDirectoryPath' => 'bar',
                    'sourceField' => 'baz',
                    'targetField' => 'baz'
                ],
                false,
                1_497_427_346,
                [42]
            ],
        ];
    }

    /**
     * @param array $configuration
     * @param bool $expected
     * @param $expectedErrorId
     * @param $expectedErrorArguments
     */
    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function isConfigurationValidReturnsCorrectValuesForInvalidConfiguration($configuration, $expected, $expectedErrorId, $expectedErrorArguments)
    {
        $this->subject->expects($this->once())
            ->method('logError')
            ->with($expectedErrorId, $expectedErrorArguments);

        $this->assertSame(
            $expected,
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function isConfigurationValidReturnsFalseForMissingDirectory()
    {
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasFolder', 'getConfiguration'])
            ->getMock();

        $configuration = [
            'storageId' => 3,
            'targetDirectoryPath' => 'foo',
            'sourceField' => 'bar',
            'targetField' => 'bar'
        ];
        $storageConfiguration = ['basePath' => 'baz'];
        $expectedErrorId = 1_497_427_363;
        $expectedErrorArguments = [$storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'], '/\\')];

        $this->storageRepository->expects($this->once())
            ->method('findByUid')
            ->with($configuration['storageId'])
            ->willReturn($this->storage);

        $this->storage->expects($this->once())
            ->method('hasFolder')
            ->with($configuration['targetDirectoryPath'])
            ->willReturn(false);
        $this->storage->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($storageConfiguration);

        $this->subject->expects($this->once())
            ->method('logError')
            ->with($expectedErrorId, $expectedErrorArguments);

        $this->subject->isConfigurationValid($configuration);
    }

    #[Test]
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasFolder', 'getConfiguration'])
            ->getMock();

        $configuration = [
            'storageId' => 3,
            'targetDirectoryPath' => 'foo',
            'sourceField' => 'bar',
            'targetField' => 'bar'
        ];

        $this->storageRepository->expects($this->once())
            ->method('findByUid')
            ->with($configuration['storageId'])
            ->willReturn($this->storage);

        $this->storage->expects($this->once())
            ->method('hasFolder')
            ->with($configuration['targetDirectoryPath'])
            ->willReturn(true);

        $this->storage->expects($this->never())
            ->method('getConfiguration');

        $this->subject->expects($this->never())
            ->method('logError');

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function getErrorCodesReturnsCorrectResult()
    {
        // Get the actual error codes from the subject
        $actualCodes = $this->subject->getErrorCodes();

        // Assert that all the required error codes are present
        $this->assertArrayHasKey(1_499_007_587, $actualCodes);
        $this->assertArrayHasKey(1_497_427_302, $actualCodes);
        $this->assertArrayHasKey(1_497_427_320, $actualCodes);
        $this->assertArrayHasKey(1_497_427_335, $actualCodes);
        $this->assertArrayHasKey(1_497_427_336, $actualCodes);
        $this->assertArrayHasKey(1_497_427_346, $actualCodes);
        $this->assertArrayHasKey(1_497_427_363, $actualCodes);

        // Check specific error messages where needed
        $this->assertEquals('Missing field name', $actualCodes[1_497_427_335][0]);
        $this->assertEquals('Missing field name', $actualCodes[1_497_427_336][0]);
        $this->assertStringContainsString('sourceField', $actualCodes[1_497_427_335][1]);
        $this->assertStringContainsString('targetField', $actualCodes[1_497_427_336][1]);
    }

    #[Test]
    public function processGetsSingleFile()
    {
        $sourceField = 'foo';
        $targetField = 'foo';
        $record = [
            $sourceField => 'bar'
        ];
        $configuration = [
            'sourceField' => 'foo',
            'targetField' => 'foo',
            'multipleRows' => false
        ];

        $fieldValue = 'bar';

        $expectedRecord = [
            $targetField => $fieldValue
        ];

        $this->subject->expects($this->once())
            ->method('getFile')
            ->with($configuration, 'bar')
            ->willReturn($fieldValue);

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    #[Test]
    public function processGetsMultipleFiles()
    {
        $sourceField = 'foo';
        $targetField = 'foo';

        $record = [
            $sourceField => 'baz,boom'
        ];

        $configuration = [
            'sourceField' => 'foo',
            'targetField' => 'foo',
            'multipleRows' => '1'
        ];

        $bazValue = 'bazValue';
        $boomValue = 'boomValue';

        $expectedRecord = [
            $targetField => [$bazValue, $boomValue]
        ];

        $this->subject->expects($this->exactly(2))
            ->method('getFile')
            ->willReturnOnConsecutiveCalls($bazValue, $boomValue);

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    #[Test]
    public function processPrefixesFilePaths()
    {
        $sourceField = 'foo';
        $targetField = 'foo';
        $prefix = 'prefix/';

        $record = [
            $sourceField => 'baz,boom'
        ];

        $configuration = [
            'sourceField' => 'foo',
            'targetField' => 'foo',
            'multipleRows' => '1',
            'sourcePath' => $prefix
        ];

        $bazValue = 'bazValue';
        $boomValue = 'boomValue';

        $expectedRecord = [
            $targetField => [$bazValue, $boomValue]
        ];

        $this->subject->expects($this->exactly(2))
            ->method('getFile')
            ->willReturnOnConsecutiveCalls($bazValue, $boomValue);

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    #[Test]
    public function processRespectsSeparator()
    {
        $sourceField = 'foo';
        $targetField = 'foo';
        $separator = '|';

        $record = [
            $sourceField => 'baz|boom'
        ];

        $configuration = [
            'sourceField' => 'foo',
            'targetField' => 'foo',
            'multipleRows' => '1',
            'separator' => $separator
        ];

        $bazValue = 'bazValue';
        $boomValue = 'boomValue';

        $expectedRecord = [
            $targetField => [$bazValue, $boomValue]
        ];

        $this->subject->expects($this->exactly(2))
            ->method('getFile')
            ->willReturnOnConsecutiveCalls($bazValue, $boomValue);

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }
}
