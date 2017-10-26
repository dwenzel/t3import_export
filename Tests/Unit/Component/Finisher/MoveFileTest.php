<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\MoveFile;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\LoggingInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use Nimut\TestingFramework\TestCase\UnitTestCase;
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
class MoveFileTest extends UnitTestCase
{
    /**
     * @var MoveFile
     */
    protected $subject;

    /**
     * @var ResourceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceFactory;

    /**
     * @var ResourceStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var Folder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $folder;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            MoveFile::class, ['logError']
        );
        $this->resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStorageObject', 'getDefaultStorage'])
            ->getMock();
        $this->subject->injectResourceFactory($this->resourceFactory);
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDefaultFolder',
                'hasFolder',
                'hasFileInFolder',
                'getFolder',
                'createFolder',
                'moveFile'
            ])
            ->getMock();
        $this->resourceFactory->expects($this->any())
            ->method('getDefaultStorage')
            ->will($this->returnValue($this->storage));
        $this->folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()->getMock();
        $this->storage->expects($this->any())
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
    }

    /**
     * Invalid configuration data provider
     * @return array
     */
    public function invalidConfigurationDataProvider()
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
     * @test
     * @param array $configuration
     * @dataProvider invalidConfigurationDataProvider
     */
    public function isConfigurationForEmptyConfigurationReturnsReturnsFalse($configuration)
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * Valid configuration data provider
     * @return array
     */
    public function validConfigurationDataProvider()
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
     * @test
     * @dataProvider validConfigurationDataProvider
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration($configuration)
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function processGetsDefaultStorageFromFactoryIfNotConfigured()
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
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->once())
        ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));

        $this->subject->process(
            $configurationWithoutStorage,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function processGetsTargetStorageFromFactoryByIdFromConfiguration()
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
        $this->resourceFactory->expects($this->once())
            ->method('getStorageObject')
            ->with((int)$storageId)
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function processGetsSourceStorageFromFactoryByIdFromConfiguration()
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
        $this->resourceFactory->expects($this->once())
            ->method('getStorageObject')
            ->with((int)$storageId)
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function processMovesFileToFolderInStorage()
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
        $sourceFile = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $sourceFolderContent = [
            $sourceFileName => $sourceFile
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);
        $this->folder->expects($this->once())
            ->method('getFiles')
            ->willReturn($sourceFolderContent);

        $this->storage->expects($this->once())
            ->method('moveFile')
            ->with(
                $sourceFile,
                $this->folder,
                $configuration['target']['name'],
                MoveFile::CONFLICT_MODE_RENAME_NEW_FILE
            );

        $this->subject->process(
            $configuration,
            $records,
            $result
        );
    }

    /**
     * @test
     */
    public function processCreatesMissingFolderFromConfiguration()
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
        $sourceFile = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $sourceFolderContent = [
            $sourceFileName => $sourceFile
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);

        $this->folder->expects($this->once())
            ->method('getFiles')
            ->willReturn($sourceFolderContent);
         $this->storage
            ->expects($this->once())
            ->method('hasFolder')
            ->with($directory)
            ->will($this->returnValue(false));
        $this->storage->expects($this->once())
            ->method('createFolder')
            ->with($directory)
            ->will($this->returnValue($this->folder));

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function processGetsSourceFolderFromConfiguration()
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
        $sourceFile = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $sourceFolderContent = [
            $sourceFileName => $sourceFile
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);

        $this->folder->expects($this->once())
            ->method('getFiles')
            ->willReturn($sourceFolderContent);
         $this->storage
            ->expects($this->once())
            ->method('hasFolder')
            ->with($directory)
            ->will($this->returnValue(true));
        $this->storage->expects($this->once())
            ->method('getFolder')
            ->with($directory)
            ->will($this->returnValue($this->folder));

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function processGetsExistingFolderFromStorage()
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
        $sourceFile = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $sourceFolderContent = [
            $sourceFileName => $sourceFile
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);

        $this->folder->expects($this->once())
            ->method('getFiles')
            ->willReturn($sourceFolderContent);

        $this->storage
            ->expects($this->once())
            ->method('hasFolder')
            ->with($directory)
            ->will($this->returnValue(true));
        $this->storage->expects($this->once())
            ->method('getFolder')
            ->with($directory)
            ->will($this->returnValue($this->folder));

        $this->subject->process(
            $configurationWithStorage,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function processRespectsConflictModeFromConfiguration()
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

        $sourceFile = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $sourceFolderContent = [
            $sourceFileName => $sourceFile
        ];
        $this->resourceFactory->expects($this->once())
            ->method('getDefaultStorage')
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->exactly(2))
            ->method('getDefaultFolder')
            ->will($this->returnValue($this->folder));
        $this->storage->expects($this->once())
            ->method('hasFileInFolder')
            ->willReturn(true);

        $this->folder->expects($this->once())
            ->method('getFiles')
            ->willReturn($sourceFolderContent);

        $this->storage
            ->expects($this->once())
            ->method('moveFile')
            ->with(
                $sourceFile,
                $this->folder,
                $configuration['target']['name'],
                $conflictMode
            );

        $this->subject->process(
            $configuration,
            $records,
            $result
        );

    }

    /**
     * @test
     */
    public function instanceImplementsLoggingInterface() {
        $this->assertInstanceOf(
            LoggingInterface::class,
            $this->subject
        );
    }
}
