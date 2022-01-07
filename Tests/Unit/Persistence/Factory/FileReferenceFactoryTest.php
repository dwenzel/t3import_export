<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockResourceFactoryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
    use MockObjectManagerTrait,
        MockResourceFactoryTrait;

    /**
     * @var FileReferenceFactory
     */
    protected $subject;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var ResourceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceFactory;

    /**
     * @var CoreFileReference|MockObject
     */
    protected CoreFileReference $coreFileReference;

    protected ExtbaseFileReference $extbaseFileReference;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = new FileReferenceFactory();
        $this->mockObjectManager();
        $this->mockResourceStorage();
        $this->mockStorageFolder();
        $this->mockResourceFactory();
        $this->coreFileReference = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()->getMock();
        $this->extbaseFileReference = $this->getMockBuilder(ExtbaseFileReference::class)
            ->setMethods(['setOriginalResource', 'setPid'])
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function createSetsOriginalResourceAndReturnsFileReference(): void
    {
        $fileId = 7;
        $configuration = [];

        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            ->willReturn($this->coreFileReference);

        $this->objectManager->expects($this->once())->method('get')
            ->with(...[ExtbaseFileReference::class])
            ->willReturn($this->extbaseFileReference);
        $this->extbaseFileReference->expects($this->once())->method('setOriginalResource')
            ->with(...[$this->coreFileReference]);

        $this->assertSame(
            $this->extbaseFileReference,
            $this->subject->create($fileId, $configuration)
        );
    }

    public function testCreateSetsInitialPageIdZero(): void
    {
        $fileId = 7;
        $configuration = [];
        $expectedPageId = 0;

        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            ->willReturn($this->coreFileReference);

        $this->objectManager->expects($this->once())->method('get')
            ->with(...[ExtbaseFileReference::class])
            ->willReturn($this->extbaseFileReference);
        $this->extbaseFileReference->expects($this->once())->method('setPid')
            ->with(...[$expectedPageId]);

        $this->subject->create($fileId, $configuration);
    }

    public function testCreateSetsPageIdFromConfiguration(): void
    {
        $fileId = 7;
        $expectedPageId = 0;

        $configuration = [
            'targetPage' => $expectedPageId
        ];

        $this->coreFileReference = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()->getMock();
        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            ->willReturn($this->coreFileReference);

        $this->objectManager->expects($this->once())->method('get')
            ->with(...[ExtbaseFileReference::class])
            ->willReturn($this->extbaseFileReference);
        $this->extbaseFileReference->expects($this->once())->method('setPid')
            ->with(...[$expectedPageId]);

        $this->subject->create($fileId, $configuration);
    }
}
