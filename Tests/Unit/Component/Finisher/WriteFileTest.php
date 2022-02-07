<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\WriteFile;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockResourceFactoryTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockResourceStorageFolderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class WriteFileTest
 */
class WriteFileTest extends TestCase
{
    use MockMessageContainerTrait,
        MockResourceFactoryTrait,
        MockResourceStorageFolderTrait;

    protected WriteFile $subject;

    protected TaskResult $result;
    protected FileInfo $fileInfo;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])
            ->getMock();
        $this->mockResourceStorage()
            ->mockResourceFactory()
            ->mockStorageFolder();
        $this->subject = new WriteFile(
            $this->resourceFactory
        );
    }

    /**
     * Invalid configuration data provider
     * @return array
     */
    public function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                []
            ],
            'empty target file name' => [
                [
                    'target' => [
                        'name' => ''
                    ]
                ]
            ],
            'target name must not be array' => [
                [
                    'target' => [
                        'name' => ['bar']
                    ]
                ]
            ],
            'target name must not be integer' => [
                [
                    'target' => [
                        'name' => 0
                    ]
                ]
            ],
            'target storage string: can not be interpreted as integer' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'storage' => 'bar'
                    ]
                ]
            ],
            'target storage array: can not be interpreted as integer' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'storage' => ['bar']
                    ]
                ]
            ],
            'target directory integer: must be string' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'directory' => 8
                    ]
                ]
            ],
            'invalid target conflictMode: foo' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => 'foo',
                    ]
                ]
            ],
            'invalid target conflictMode: empty string' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => '',
                    ]
                ]
            ],
            'invalid target conflictMode: array' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => [],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider invalidConfigurationDataProvider
     * @param array $configuration
     */
    public function testIsConfigurationForEmptyConfigurationReturnsReturnsFalse(array $configuration): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * Valid configuration data provider
     * @return array
     */
    public function validConfigurationDataProvider(): array
    {
        return [
            'minimal: only file name' => [
                [
                    'target' => [
                        'name' => 'bar'
                    ]
                ]
            ],
            'file name and valid conflictMode: cancel' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => WriteFile::CONFLICT_MODE_CANCEL
                    ]
                ]
            ],
            'file name and valid conflictMode: changeName' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => WriteFile::CONFLICT_MODE_CHANGENAME
                    ]
                ]
            ],
            'file name and valid conflictMode: replace' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => WriteFile::CONFLICT_MODE_REPLACE
                    ]
                ]
            ],
            'target storage string: can be interpreted as integer' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'storage' => '3'
                    ]
                ]
            ],
            'target storage integer' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'storage' => 5
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider validConfigurationDataProvider
     * @param array $configuration
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testProcessReturnsFalseIfResultNotInstanceOfTaskResult(): void
    {
        $result = [];
        $records = [];

        $this->assertFalse(
            $this->subject->process([], $records, $result)
        );
    }

    public function testProcessReturnsFalseIfResultDoesNotContainFileInfo(): void
    {
        $this->result->expects($this->once())->method('getInfo')
            ->willReturn(null);

        $records = [];

        $this->assertFalse(
            $this->subject->process([], $records, $this->result)
        );
    }

    public function testProcessGetsDefaultStorageFromFactoryIfNotConfigured(): void
    {
        $records = [];
        $configurationWithoutStorage = [
            'target' => [
                'name' => 'bar.xml'
            ]
        ];
        $result = $this->expectDefaultFolderAccess();

        $this->subject->process(
            $configurationWithoutStorage,
            $records,
            $result
        );

    }

    /**
     * @return TaskResult|MockObject
     */
    protected function expectDefaultFolderAccess()
    {
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage');
        $this->resourceStorage->expects($this->once())
            ->method('getDefaultFolder');
        return $result;
    }

    public function testProcessGetsStorageFromFactoryByIdFromConfiguration(): void
    {
        $records = [];
        $storageId = '5';
        $configurationWithStorage = [
            'target' => [
                'name' => 'bar.xml',
                'storage' => $storageId
            ]
        ];
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->resourceFactory->expects($this->once())
            ->method('getStorageObject')
            ->with(...[(int)$storageId])
            ->willReturn($this->resourceStorage);
        $this->resourceStorage->expects($this->once())
            ->method('getDefaultFolder')
            ->willReturn($this->folder);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    public function testProcessAddsFileToFolderInStorage(): void
    {
        $records = [];
        $fileName = 'bar.xml';
        $configuration = [
            'target' => [
                'name' => $fileName
            ]
        ];
        $realPath = 'foobar';
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealPath'])->getMock();
        $fileInfo->expects($this->once())
            ->method('getRealPath')
            ->willReturn($realPath);

        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);

        $this->resourceStorage->expects($this->once())
            ->method('addFile')
            ->with(...
                [
                    $realPath,
                    $this->folder,
                    $fileName
                ]
            );

        $this->subject->process(
            $configuration,
            $records,
            $result
        );
    }

    public function testProcessCreatesMissingFolderFromConfiguration(): void
    {
        $records = [];
        $directory = 'baz';
        $configurationWithStorage = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory
            ]
        ];

        $result = $this->expectCreationOfMissingDirectory($directory);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @param string $directory
     * @return TaskResult|MockObject
     */
    protected function expectCreationOfMissingDirectory(string $directory)
    {
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->resourceStorage
            ->expects($this->once())
            ->method('hasFolder')
            ->with(...[$directory])
            ->willReturn(false);
        $this->resourceStorage->expects($this->once())
            ->method('createFolder')
            ->with(...[$directory])
            ->willReturn($this->folder);
        return $result;
    }

    public function testProcessGetsExistingFolderFromStorage(): void
    {
        $records = [];
        $directory = 'baz';
        $configurationWithStorage = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory
            ]
        ];
        $result = $this->expectAccessOfExistingDirectory($directory);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @param string $directory
     * @return TaskResult|MockObject
     */
    protected function expectAccessOfExistingDirectory(string $directory)
    {
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->resourceStorage
            ->expects($this->once())
            ->method('hasFolder')
            ->with(...[$directory])
            ->willReturn(true);
        $this->resourceStorage->expects($this->once())
            ->method('getFolder')
            ->with(...[$directory])
            ->willReturn($this->folder);
        return $result;
    }

    public function testProcessRespectsConflictModeFromConfiguration(): void
    {
        $records = [];
        $conflictMode = WriteFile::CONFLICT_MODE_REPLACE;
        $configuration = [
            'target' => [
                'name' => 'bar.xml',
                'conflictMode' => $conflictMode
            ]
        ];
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->resourceStorage
            ->expects($this->once())
            ->method('addFile')
            ->with(...[
                    null,
                    $this->folder,
                    $configuration['target']['name'],
                    $conflictMode
                ]
            )
            ->willReturn(false);

        $this->subject->process(
            $configuration,
            $records,
            $result
        );

    }
}
