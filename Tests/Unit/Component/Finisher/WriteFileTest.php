<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\WriteFile;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\StorageRepository;
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
    private const string REAL_PATH = '/tmp/t3import_export_write_file_test.xml';

    protected WriteFile $subject;
    protected TaskResult $result;
    protected FileInfo $fileInfo;
    protected StorageRepository|MockObject $storageRepository;
    protected ResourceStorage|MockObject $resourceStorage;
    protected Folder|MockObject $folder;
    protected MessageContainer|MockObject $messageContainer;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->fileInfo = $this->createFileInfo();

        $this->result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])
            ->getMock();

        $this->resourceStorage = $this->createMock(ResourceStorage::class);
        $this->resourceStorage->method('isOnline')->willReturn(true);

        $this->folder = $this->createMock(Folder::class);
        $this->resourceStorage->method('getDefaultFolder')
            ->willReturn($this->folder);

        $this->storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getStorageObject',
                'getDefaultStorage',
            ])
            ->getMock();

        $this->storageRepository->method('getDefaultStorage')
            ->willReturn($this->resourceStorage);

        $this->messageContainer = $this->createMock(MessageContainer::class);

        $this->subject = new WriteFile(
            $this->storageRepository
        );
    }

    /**
     * Invalid configuration data provider
     */
    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                [],
            ],
            'empty target file name' => [
                [
                    'target' => [
                        'name' => '',
                    ],
                ],
            ],
            'target name must not be array' => [
                [
                    'target' => [
                        'name' => ['bar'],
                    ],
                ],
            ],
            'target name must not be integer' => [
                [
                    'target' => [
                        'name' => 0,
                    ],
                ],
            ],
            'target storage string: can not be interpreted as integer' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'storage' => 'bar',
                    ],
                ],
            ],
            'target storage array: can not be interpreted as integer' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'storage' => ['bar'],
                    ],
                ],
            ],
            'target directory integer: must be string' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'directory' => 8,
                    ],
                ],
            ],
            'invalid target conflictMode: foo' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => 'foo',
                    ],
                ],
            ],
            'invalid target conflictMode: empty string' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => '',
                    ],
                ],
            ],
            'invalid target conflictMode: array' => [
                [
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => [],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('invalidConfigurationDataProvider')]
    public function testIsConfigurationForEmptyConfigurationReturnsReturnsFalse(array $configuration): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * Valid configuration data provider
     */
    public static function validConfigurationDataProvider(): array
    {
        return [
            'minimal: only file name' => [
                [
                    'target' => [
                        'name' => 'bar',
                    ],
                ],
            ],
            'file name and valid conflictMode: cancel' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => WriteFile::CONFLICT_MODE_CANCEL,
                    ],
                ],
            ],
            'file name and valid conflictMode: changeName' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => WriteFile::CONFLICT_MODE_CHANGENAME,
                    ],
                ],
            ],
            'file name and valid conflictMode: replace' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => WriteFile::CONFLICT_MODE_REPLACE,
                    ],
                ],
            ],
            'target storage string: can be interpreted as integer' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'storage' => '3',
                    ],
                ],
            ],
            'target storage integer' => [
                [
                    'target' => [
                        'name' => 'bar',
                        'storage' => 5,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('validConfigurationDataProvider')]
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
                'name' => 'bar.xml',
            ],
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
        $fileInfo = $this->createFileInfo();
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->storageRepository->expects($this->once())
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
                'storage' => $storageId,
            ],
        ];
        $fileInfo = $this->createFileInfo();
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->storageRepository->expects($this->once())
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
                'name' => $fileName,
            ],
        ];
        $realPath = 'foobar';
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRealPath'])->getMock();
        $fileInfo->expects($this->once())
            ->method('getRealPath')
            ->willReturn($realPath);

        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);

        $this->resourceStorage->expects($this->once())
            ->method('addFile')
            ->with(
                ...
                [
                    $realPath,
                    $this->folder,
                    $fileName,
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
                'directory' => $directory,
            ],
        ];

        $result = $this->expectCreationOfMissingDirectory($directory);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );
    }

    /**
     * @return TaskResult|MockObject
     */
    protected function expectCreationOfMissingDirectory(string $directory)
    {
        $fileInfo = $this->createFileInfo();
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
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
                'directory' => $directory,
            ],
        ];
        $result = $this->expectAccessOfExistingDirectory($directory);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );
    }

    /**
     * @return TaskResult|MockObject
     */
    protected function expectAccessOfExistingDirectory(string $directory)
    {
        $fileInfo = $this->createFileInfo();
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
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
                'conflictMode' => $conflictMode,
            ],
        ];
        $fileInfo = $this->createFileInfo();
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->willReturn($fileInfo);
        $this->resourceStorage
            ->expects($this->once())
            ->method('addFile')
            ->with(
                ...[
                    self::REAL_PATH,
                    $this->folder,
                    $configuration['target']['name'],
                    DuplicationBehavior::REPLACE,
                ]
            );

        $this->subject->process(
            $configuration,
            $records,
            $result
        );
    }

    /**
     * @return array<string, array{string|null, DuplicationBehavior}>
     */
    public static function conflictModeDataProvider(): array
    {
        return [
            'not configured' => [null, DuplicationBehavior::RENAME],
            WriteFile::CONFLICT_MODE_CANCEL => [WriteFile::CONFLICT_MODE_CANCEL, DuplicationBehavior::CANCEL],
            WriteFile::CONFLICT_MODE_CHANGENAME => [WriteFile::CONFLICT_MODE_CHANGENAME, DuplicationBehavior::RENAME],
            WriteFile::CONFLICT_MODE_REPLACE => [WriteFile::CONFLICT_MODE_REPLACE, DuplicationBehavior::REPLACE],
        ];
    }

    #[DataProvider('conflictModeDataProvider')]
    public function testProcessMapsConflictModeToDuplicationBehavior(?string $conflictMode, DuplicationBehavior $expected): void
    {
        $records = [];
        $configuration = ['target' => ['name' => 'bar.xml']];
        if ($conflictMode !== null) {
            $configuration['target']['conflictMode'] = $conflictMode;
        }
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->method('getInfo')->willReturn($this->fileInfo);
        $this->resourceStorage->expects($this->once())
            ->method('addFile')
            ->with($this->anything(), $this->anything(), $this->anything(), $expected);

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessThrowsExceptionIfFileToWriteDoesNotExist(): void
    {
        $records = [];
        $configuration = ['target' => ['name' => 'bar.xml']];
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRealPath', 'getPathname'])
            ->getMock();
        $fileInfo->method('getRealPath')->willReturn(false);
        $fileInfo->method('getPathname')->willReturn('missing.xml');
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->method('getInfo')->willReturn($fileInfo);
        $this->resourceStorage->expects($this->never())->method('addFile');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1763130000);

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessDoesNotAccessDefaultFolderIfDirectoryIsConfigured(): void
    {
        $records = [];
        $directory = 'baz';
        $configuration = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory,
            ],
        ];
        $result = $this->expectAccessOfExistingDirectory($directory);
        $this->resourceStorage->expects($this->never())
            ->method('getDefaultFolder');

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessUsesDefaultFolderIfDirectoryIsEmpty(): void
    {
        $records = [];
        $configuration = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => '',
            ],
        ];
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->method('getInfo')->willReturn($this->fileInfo);
        $this->resourceStorage->expects($this->never())->method('hasFolder');
        $this->resourceStorage->expects($this->once())
            ->method('addFile')
            ->with($this->anything(), $this->folder, 'bar.xml', $this->anything());

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessThrowsExceptionIfStorageIsOffline(): void
    {
        $records = [];
        $configuration = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => 'baz',
            ],
        ];
        $storage = $this->createMock(ResourceStorage::class);
        $storage->method('isOnline')->willReturn(false);
        $storage->expects($this->never())->method('getDefaultFolder');
        $storage->expects($this->never())->method('addFile');
        $storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultStorage', 'getStorageObject'])
            ->getMock();
        $storageRepository->method('getDefaultStorage')->willReturn($storage);
        $subject = new WriteFile($storageRepository);
        $result = $this->getMockBuilder(TaskResult::class)
            ->onlyMethods(['getInfo'])->getMock();
        $result->method('getInfo')->willReturn($this->fileInfo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1762171200);

        $subject->process($configuration, $records, $result);
    }

    /**
     * FileInfo double with a real path, as addFile() requires a string
     */
    protected function createFileInfo(): FileInfo|MockObject
    {
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRealPath'])
            ->getMock();
        $fileInfo->method('getRealPath')->willReturn(self::REAL_PATH);

        return $fileInfo;
    }
}
