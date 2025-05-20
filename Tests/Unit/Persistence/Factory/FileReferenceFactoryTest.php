<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************
 *
 * /**
 * Class FileReferenceFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory
 */
class FileReferenceFactoryTest extends TestCase
{
    protected FileReferenceFactory $subject;

    /**
     * @var CoreFileReference|MockObject
     */
    protected CoreFileReference|MockObject $coreFileReference;

    protected ExtbaseFileReference|MockObject $extbaseFileReference;

    /**
     * @var ResourceStorage|MockObject
     */
    protected ResourceStorage|MockObject $resourceStorage;

    /**
     * @var Folder|MockObject
     */
    protected Folder|MockObject $folder;

    /**
     * @var ResourceFactory|MockObject
     */
    protected ResourceFactory|MockObject $resourceFactory;

    /**
     * Creates a mock resource storage
     */
    protected function mockResourceStorage(): void
    {
        $this->resourceStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'addFile',
                'getConfiguration',
                'getDefaultFolder',
                'getFile',
                'hasFile',
                'hasFolder',
                'hasFileInFolder',
                'getFolder',
                'createFolder',
                'moveFile',
            ])
            ->getMock();
    }

    /**
     * Creates a mock storage folder
     */
    protected function mockStorageFolder(): void
    {
        $this->folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceStorage->method('getDefaultFolder')
            ->willReturn($this->folder);
    }

    /**
     * Creates a mock resource factory
     */
    protected function mockResourceFactory(): void
    {
        $this->resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getStorageObject',
                'getDefaultStorage',
                'createFileReferenceObject',
            ])
            ->getMock();
        $this->resourceFactory->method('getDefaultStorage')
            ->willReturn($this->resourceStorage);
    }

    /**
     * set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->mockResourceStorage();
        $this->mockStorageFolder();
        $this->mockResourceFactory();

        $this->subject = new FileReferenceFactory(
            $this->resourceFactory
        );

        $this->coreFileReference = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()->getMock();
        $this->extbaseFileReference = $this->getMockBuilder(ExtbaseFileReference::class)
            ->onlyMethods(['setOriginalResource', 'setPid'])
            ->disableOriginalConstructor()->getMock();
    }

    #[Test]
    public function createSetsOriginalResourceAndReturnsFileReference(): void
    {
        $fileId = 7;
        $configuration = [];

        /** @noinspection ClassConstantUsageCorrectnessInspection */
        GeneralUtility::addInstance(FileReference::class, $this->extbaseFileReference);
        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            ->willReturn($this->coreFileReference);

        $this->extbaseFileReference->expects($this->once())
            ->method('setOriginalResource')
            ->with(...[$this->coreFileReference]);
        $this->subject->createFileReferenceObject($fileId, $configuration);
    }

    #[Test]
    public function testCreateSetsInitialPageIdZero(): void
    {
        $fileId = 7;
        $configuration = [];
        $expectedPageId = 0;

        /** @noinspection ClassConstantUsageCorrectnessInspection */
        GeneralUtility::addInstance(FileReference::class, $this->extbaseFileReference);

        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            ->willReturn($this->coreFileReference);
        $this->extbaseFileReference->expects($this->once())->method('setPid')
            ->with(...[$expectedPageId]);

        $this->subject->createFileReferenceObject($fileId, $configuration);
    }

    #[Test]
    public function testCreateSetsPageIdFromConfiguration(): void
    {
        $fileId = 7;
        $expectedPageId = 0;

        $configuration = [
            'targetPage' => $expectedPageId,
        ];

        /** @noinspection ClassConstantUsageCorrectnessInspection */
        GeneralUtility::addInstance(FileReference::class, $this->extbaseFileReference);

        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            ->willReturn($this->coreFileReference);
        $this->extbaseFileReference->expects($this->once())->method('setPid')
            ->with(...[$expectedPageId]);

        $this->subject->createFileReferenceObject($fileId, $configuration);
    }
}
