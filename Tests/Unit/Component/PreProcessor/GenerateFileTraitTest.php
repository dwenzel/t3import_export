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
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateFileResourceTest
 */
class GenerateFileTraitTest extends TestCase
{
    /**
     * @var GenerateFileTrait |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var ResourceStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var StorageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storageRepository;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(GenerateFileTrait::class)
            ->setMethods(['logError'])->getMockForTrait();

        $this->storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByUid'])->getMock();

        $this->subject->injectStorageRepository($this->storageRepository);
    }


    /**
     * Provides dependencies for injection tests
     */
    public function dependenciesDataProvider()
    {
        return [
            [FilePathFactory::class, 'filePathFactory']
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


    public function invalidConfigurationDataProvider()
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
            // missing field name
            [
                [
                    'targetDirectoryPath' => 'bar'
                ],
                false,
                1_497_427_335,
                null
            ],
            // missing storage id
            [
                [
                    'targetDirectoryPath' => 'bar',
                    'fieldName' => 'baz'
                ],
                false,
                1_497_427_302,
                null
            ],
            // missing resourceStorage
            [
                [
                    'storageId' => 'foo',
                    'targetDirectoryPath' => 'bar',
                    'fieldName' => 'baz'
                ],
                false,
                1_497_427_346,
                ['foo']
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidConfigurationDataProvider
     * @param array $configuration
     * @param bool $expected
     * @param $expectedErrorId
     * @param $expectedErrorArguments
     */
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

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseForMissingDirectory()
    {
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasFolder', 'getConfiguration'])->getMock();

        $configuration = [
            'storageId' => 3,
            'targetDirectoryPath' => 'foo',
            'fieldName' => 'bar'
        ];
        $storageConfiguration = ['basePath' => 'baz'];
        $expectedErrorId = 1_497_427_363;
        $expectedErrorArguments = [$storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'], '/\\')];

        $this->storageRepository->expects($this->once())
            ->method('findByUid')
            ->with($configuration['storageId'])
            ->will($this->returnValue($this->storage));

        $this->storage->expects($this->once())
            ->method('hasFolder')
            ->with($configuration['targetDirectoryPath'])
            ->will($this->returnValue(false));
        $this->storage->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($storageConfiguration));

        $this->subject->expects($this->once())
            ->method('logError')
            ->with($expectedErrorId, $expectedErrorArguments);

        $this->subject->isConfigurationValid($configuration);
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasFolder', 'getConfiguration'])->getMock();

        $configuration = [
            'storageId' => 3,
            'targetDirectoryPath' => 'foo',
            'fieldName' => 'bar'
        ];

        $this->storageRepository->expects($this->once())
            ->method('findByUid')
            ->with($configuration['storageId'])
            ->will($this->returnValue($this->storage));

        $this->storage->expects($this->once())
            ->method('hasFolder')
            ->with($configuration['targetDirectoryPath'])
            ->will($this->returnValue(true));

        $this->storage->expects($this->never())
            ->method('getConfiguration');

        $this->subject->expects($this->never())
            ->method('logError');

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function getErrorCodesReturnsCorrectResult()
    {
        $expectedCodes = $errors = [
            1_499_007_587 => ['Empty configuration', 'Configuration must not be empty'],
            1_497_427_302 => ['Missing storage id', 'config[\'storageId\'] must be set'],
            1_497_427_320 => ['Missing target directory ', 'config[\'targetDirectoryPath\` must be set'],
            1_497_427_335 => ['Missing field name', 'config[\'fieldName\'] must be set'],
            1_497_427_346 => ['Invalid storage', 'Could not find storage with id %s given in $config[\'storageId\']'],
            1_497_427_363 => ['Missing directory', 'Directory %s given in $config[\'basePath\'] and $config[\'targetDirectory\'] does not exist.']
        ];

        $this->assertSame(
            $expectedCodes,
            $this->subject->getErrorCodes()
        );
    }

    /**
     * @test
     */
    public function processGetsSingleFile()
    {
        $fieldName = 'foo';
        $record = [
            $fieldName => 'bar'
        ];
        $configuration = [
            'fieldName' => 'foo'
        ];

        $fieldValue = 'bar';

        $expectedRecord = [
            $fieldName => $fieldValue
        ];

        $this->subject->expects($this->once())
            ->method('getFile')
            ->with($configuration, 'bar')
            ->will($this->returnValue($fieldValue));

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    /**
     * @test
     */
    public function processGetsMultipleFiles()
    {
        $fieldName = 'foo';

        $record = [
            $fieldName => 'baz,boom'
        ];

        $configuration = [
            'fieldName' => 'foo',
            'multipleRows' => '1'
        ];

        $bazValue = 'bazValue';
        $boomValue = 'boomValue';


        $expectedRecord = [
            $fieldName => [$bazValue, $boomValue]
        ];

        $this->subject->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                [$configuration, 'baz'],
                [$configuration, 'boom']
            )
            ->will($this->onConsecutiveCalls($bazValue, $boomValue));

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    /**
     * @test
     */
    public function processPrefixesFilePaths()
    {
        $fieldName = 'foo';
        $prefix = 'prefix/';

        $record = [
            $fieldName => 'baz,boom'
        ];

        $configuration = [
            'fieldName' => 'foo',
            'multipleRows' => '1',
            'sourcePath' => $prefix
        ];

        $bazPath = $prefix . 'baz';
        $boomPath = $prefix . 'boom';

        $bazValue = 'bazValue';
        $boomValue = 'boomValue';


        $expectedRecord = [
            $fieldName => [$bazValue, $boomValue]
        ];

        $this->subject->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                [$configuration, $bazPath],
                [$configuration, $boomPath]
            )
            ->will($this->onConsecutiveCalls($bazValue, $boomValue));

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }

    /**
     * @test
     */
    public function processRespectsSeparator()
    {
        $fieldName = 'foo';
        $separator = '|';

        $record = [
            $fieldName => 'baz|boom'
        ];

        $configuration = [
            'fieldName' => 'foo',
            'multipleRows' => '1',
            'separator' => $separator
        ];


        $bazValue = 'bazValue';
        $boomValue = 'boomValue';


        $expectedRecord = [
            $fieldName => [$bazValue, $boomValue]
        ];

        $this->subject->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                [$configuration, 'baz'],
                [$configuration, 'boom']
            )
            ->will($this->onConsecutiveCalls($bazValue, $boomValue));

        $this->subject->process($configuration, $record);

        $this->assertSame(
            $expectedRecord,
            $record
        );
    }
}
