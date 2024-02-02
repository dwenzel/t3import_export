<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\MoveFile;
use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockResourceFactoryTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockResourceStorageFolderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\FileInterface;
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
 * Class MoveFileTest
 */
class MoveFileTest extends TestCase
{
    use MockMessageContainerTrait,
        MockResourceFactoryTrait,
        MockResourceStorageFolderTrait;

    protected MoveFile $subject;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this
            ->mockResourceStorage()
            ->mockResourceFactory()
            ->mockStorageFolder()
            ->mockMessageContainer();
        $this->subject = new MoveFile($this->resourceFactory, $this->messageContainer);
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
            'target not set' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'source not set' => [
                [
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'empty target file name' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => ''
                    ]
                ]
            ],
            'empty source file name' => [
                [
                    'source' => [
                        'name' => ''
                    ],
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'target name must not be array' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => ['bar']
                    ]
                ]
            ],
            'target name must not be integer' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 0
                    ]
                ]
            ],
            'source name must not be array' => [
                [
                    'source' => [
                        'name' => ['bar']
                    ],
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'source name must not be integer' => [
                [
                    'source' => [
                        'name' => 0
                    ],
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'target storage string: can not be interpreted as integer' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'foo',
                        'storage' => 'bar'
                    ]
                ]
            ],
            'target storage array: can not be interpreted as integer' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'foo',
                        'storage' => ['bar']
                    ]
                ]
            ],
            'source storage string: can not be interpreted as integer' => [
                [
                    'source' => [
                        'name' => 'foo',
                        'storage' => 'bar'
                    ],
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'source storage array: can not be interpreted as integer' => [
                [
                    'source' => [
                        'name' => 'foo',
                        'storage' => ['bar']
                    ],
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'target directory integer: must be string' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'foo',
                        'directory' => 8
                    ]
                ]
            ],
            'source directory integer: must be string' => [
                [
                    'source' => [
                        'name' => 'foo',
                        'directory' => 8
                    ],
                    'target' => [
                        'name' => 'foo'
                    ]
                ]
            ],
            'invalid target conflictMode: foo' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => 'foo',
                    ]
                ]
            ],
            'invalid target conflictMode: empty string' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'foo',
                        'conflictMode' => '',
                    ]
                ]
            ],
            'invalid target conflictMode: array' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
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
            'minimal: only file names' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'bar'
                    ]
                ]
            ],
            'file name and valid conflictMode: cancel' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => MoveFile::CONFLICT_MODE_CANCEL
                    ]
                ]
            ],
            'file name and valid conflictMode: overrideExistingFile' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => MoveFile::CONFLICT_MODE_OVERRIDE_EXISTING_FILE
                    ]
                ]
            ],
            'file name and valid conflictMode: renameNewFile' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'bar',
                        'conflictMode' => MoveFile::CONFLICT_MODE_RENAME_NEW_FILE
                    ]
                ]
            ],
            'target storage string: can be interpreted as integer' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
                    'target' => [
                        'name' => 'bar',
                        'storage' => '3'
                    ]
                ]
            ],
            'target storage integer' => [
                [
                    'source' => [
                        'name' => 'foo'
                    ],
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
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration($configuration): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testProcessGetsDefaultStorageFromFactoryIfNotConfigured(): void
    {
        $records = [];
        $configurationWithoutStorage = [
            'source' => [
                'name' => 'foo.xml'
            ],
            'target' => [
                'name' => 'bar.xml'
            ]
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->willReturn($this->resourceStorage);
        $this->resourceStorage->expects($this->once())
            ->method('getDefaultFolder')
            ->willReturn($this->folder);

        $this->subject->process(
            $configurationWithoutStorage,
            $records,
            $result
        );

    }

    public function testProcessGetsTargetStorageFromFactoryByIdFromConfiguration(): void
    {
        $records = [];
        $storageId = '5';
        $configurationWithStorage = [
            'source' => [
                'name' => 'foo.xml'
            ],
            'target' => [
                'name' => 'bar.xml',
                'storage' => $storageId
            ]
        ];
        $this->expectExistingFile($storageId);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @param string $storageId
     */
    protected function expectExistingFile(string $storageId): void
    {
        $this->resourceFactory->expects($this->once())
            ->method('getStorageObject')
            ->with(...[(int)$storageId])
            ->willReturn($this->resourceStorage);
        $this->resourceStorage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->willReturn($this->folder);
        $this->resourceStorage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);
    }

    public function testProcessGetsSourceStorageFromFactoryByIdFromConfiguration(): void
    {
        $records = [];
        $storageId = '5';
        $configurationWithStorage = [
            'source' => [
                'name' => 'foo.xml',
                'storage' => $storageId
            ],
            'target' => [
                'name' => 'bar.xml'
            ]
        ];
        $this->expectExistingFile($storageId);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    public function testProcessMovesFileToFolderInStorage(): void
    {
        $records = [];
        $sourceFileName = 'foo.xml';

        $fileName = 'bar.xml';
        $configuration = [
            'source' => [
                'name' => $sourceFileName
            ],
            'target' => [
                'name' => $fileName
            ]
        ];
        $sourceFile = $this->mockSourceFile($sourceFileName);

        $this->resourceStorage->expects($this->once())
            ->method('moveFile')
            ->with(...[
                    $sourceFile,
                    $this->folder,
                    $configuration['target']['name'],
                    MoveFile::CONFLICT_MODE_RENAME_NEW_FILE
                ]
            );

        $this->subject->process(
            $configuration,
            $records,
            $result
        );
    }

    /**
     * @param string $sourceFileName
     * @return MockObject|FileInterface
     */
    protected function mockSourceFile(string $sourceFileName)
    {
        $sourceFile = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $sourceFolderContent = [
            $sourceFileName => $sourceFile
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->willReturn($this->resourceStorage);
        $this->resourceStorage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->willReturn($this->folder);
        $this->resourceStorage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);
        $this->folder->expects($this->once())
            ->method('getFiles')
            ->willReturn($sourceFolderContent);
        return $sourceFile;
    }

    public function testProcessCreatesMissingFolderFromConfiguration(): void
    {
        $records = [];
        $sourceFileName = 'foo.xml';
        $directory = 'baz';
        $configurationWithStorage = [
            'source' => [
                'name' => $sourceFileName
            ],
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory
            ]
        ];
        $this->mockSourceFile($sourceFileName);
        $this->resourceStorage
            ->expects($this->once())
            ->method('hasFolder')
            ->with(...[$directory])
            ->willReturn(false);
        $this->resourceStorage->expects($this->once())
            ->method('createFolder')
            ->with(...[$directory])
            ->willReturn($this->folder);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );
    }

    public function testProcessGetsSourceFolderFromConfiguration(): void
    {
        $records = [];
        $sourceFileName = 'foo.xml';
        $directory = 'baz';
        $configurationWithStorage = [
            'source' => [
                'name' => $sourceFileName,
                'directory' => $directory
            ],
            'target' => [
                'name' => 'bar.xml'
            ]
        ];
        $this->mockSourceFile($sourceFileName);
        $this->expectExistingFolderAccess($directory);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @param string $directory
     */
    protected function expectExistingFolderAccess(string $directory): void
    {
        $this->resourceStorage
            ->expects($this->once())
            ->method('hasFolder')
            ->with(...[$directory])
            ->willReturn(true);
        $this->resourceStorage->expects($this->once())
            ->method('getFolder')
            ->with(...[$directory])
            ->willReturn($this->folder);
    }

    public function testProcessGetsExistingFolderFromStorage(): void
    {
        $records = [];
        $sourceFileName = 'foo.xml';
        $directory = 'baz';
        $configurationWithStorage = [
            'source' => [
                'name' => $sourceFileName
            ],
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory
            ]
        ];
        $this->mockSourceFile($sourceFileName);
        $this->expectExistingFolderAccess($directory);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    public function testProcessRespectsConflictModeFromConfiguration(): void
    {
        $records = [];
        $conflictMode = MoveFile::CONFLICT_MODE_OVERRIDE_EXISTING_FILE;
        $sourceFileName = 'foo.xml';
        $configuration = [
            'source' => [
                'name' => $sourceFileName
            ],
            'target' => [
                'name' => 'bar.xml',
                'conflictMode' => $conflictMode
            ]
        ];

        $sourceFile = $this->mockSourceFile($sourceFileName);

        $this->resourceStorage
            ->expects($this->once())
            ->method('moveFile')
            ->with(...[
                    $sourceFile,
                    $this->folder,
                    $configuration['target']['name'],
                    $conflictMode
                ]
            );

        $this->subject->process(
            $configuration,
            $records,
            $result
        );

    }

    public function testInstanceImplementsLoggingInterface(): void
    {
        $this->assertInstanceOf(
            LoggingInterface::class,
            $this->subject
        );
    }

    public function testGetErrorCodesReturnsClassConstant(): void
    {
        $this->assertSame(
            MoveFile::ERROR_CODES,
            $this->subject->getErrorCodes()
        );
    }

    public function testGetNoticeCodesReturnsClassConstant(): void
    {
        $this->assertSame(
            MoveFile::NOTICE_CODES,
            $this->subject->getNoticeCodes()
        );
    }

}
