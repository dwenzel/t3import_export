<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\WriteFile;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
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
 * Class WriteFileTest
 */
class WriteFileTest extends UnitTestCase
{
    /**
     * @var WriteFile
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
            WriteFile::class, ['dummy']
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
                'getFolder',
                'createFolder',
                'addFile'
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
    public function processReturnsFalseIfResultNotInstanceOfTaskResult()
    {
        $result = [];
        $records = [];

        $this->assertFalse(
            $this->subject->process([], $records, $result)
        );
    }

    /**
     * @test
     */
    public function processReturnsFalseIfResultDoesNotContainFileInfo()
    {
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->once())->method('getInfo')
            ->will($this->returnValue(null));

        $records = [];
        $expected = $result;

        $this->assertFalse(
            $this->subject->process([], $records, $result)
        );
    }

    /**
     * @test
     */
    public function processGetsDefaultStorageFromFactoryIfNotConfigured()
    {
        $records = [];
        $configurationWithoutStorage = [
            'target' => [
                'name' => 'bar.xml'
            ]
        ];
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->will($this->returnValue($fileInfo));
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
    public function processGetsStorageFromFactoryByIdFromConfiguration()
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
            ->will($this->returnValue($fileInfo));
        $this->resourceFactory->expects($this->once())
            ->method('getStorageObject')
            ->with((int)$storageId)
            ->will($this->returnValue($this->storage));
        $this->storage->expects($this->once())
            ->method('getDefaultFolder')
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
    public function processAddsFileToFolderInStorage()
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
            ->will($this->returnValue($realPath));

        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->will($this->returnValue($fileInfo));

        $this->storage->expects($this->once())
            ->method('addFile')
            ->with(
                $realPath,
                $this->folder,
                $fileName
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
        $directory = 'baz';
        $configurationWithStorage = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory
            ]
        ];
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->will($this->returnValue($fileInfo));
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
    public function processGetsExistingFolderFromStorage()
    {
        $records = [];
        $directory = 'baz';
        $configurationWithStorage = [
            'target' => [
                'name' => 'bar.xml',
                'directory' => $directory
            ]
        ];
        $fileInfo = $this->getMockBuilder(FileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $result = $this->getMockBuilder(TaskResult::class)
            ->setMethods(['getInfo'])->getMock();
        $result->expects($this->atLeast(1))->method('getInfo')
            ->will($this->returnValue($fileInfo));
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
            ->will($this->returnValue($fileInfo));
        $this->storage
            ->expects($this->once())
            ->method('addFile')
            ->with(
                null,
                $this->folder,
                $configuration['target']['name'],
                $conflictMode
            )
            ->will($this->returnValue(false));

        $this->subject->process(
            $configuration,
            $records,
            $result
        );

    }
}
