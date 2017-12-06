<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
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

/**
 * Class FileReferenceFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory
 */
class FileReferenceFactoryTest extends UnitTestCase
{

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
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(FileReferenceFactory::class)
            ->setMethods(['dummy'])->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])->getMockForAbstractClass();
        $this->subject->injectObjectManager($this->objectManager);
        $this->resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()->setMethods(['createFileReferenceObject'])
            ->getMock();
        $this->subject->injectResourceFactory($this->resourceFactory);
    }

    /**
     * @test
     */
    public function createSetsOriginalResourceAndReturnsFileReference()
    {
        $fileId = 7;
        $configuration = [];

        $mockCoreFileReference = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()->getMock();
        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
            //->with($this->expect(['uid_local' => $fileId]));
        ->will($this->returnValue($mockCoreFileReference));

        $mockFileReference = $this->getMockBuilder(FileReference::class)
            ->setMethods(['setOriginalResource'])
            ->disableOriginalConstructor()->getMock();
        $this->objectManager->expects($this->once())->method('get')
            ->with(FileReference::class)
            ->will($this->returnValue($mockFileReference));
        $mockFileReference->expects($this->once())->method('setOriginalResource')
            ->with($mockCoreFileReference);

        $this->assertSame(
            $mockFileReference,
            $this->subject->create($fileId, $configuration)
        );
    }

    /**
     * @test
     */
    public function createSetsInitialPageIdZero()
    {
        $fileId = 7;
        $configuration = [];
        $expectedPageId = 0;

        $mockCoreFileReference = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()->getMock();
        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
        ->will($this->returnValue($mockCoreFileReference));

        $mockFileReference = $this->getMockBuilder(FileReference::class)
            ->setMethods(['setPid', 'setOriginalResource'])
            ->disableOriginalConstructor()->getMock();
        $this->objectManager->expects($this->once())->method('get')
            ->with(FileReference::class)
            ->will($this->returnValue($mockFileReference));
        $mockFileReference->expects($this->once())->method('setPid')
            ->with($expectedPageId);

        $this->subject->create($fileId, $configuration);
    }

    /**
     * @test
     */
    public function createSetsPageIdFromConfiguration()
    {
        $fileId = 7;
        $expectedPageId = 0;

        $configuration = [
            'targetPage' => $expectedPageId
        ];

        $mockCoreFileReference = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()->getMock();
        $this->resourceFactory->expects($this->once())->method('createFileReferenceObject')
        ->will($this->returnValue($mockCoreFileReference));

        $mockFileReference = $this->getMockBuilder(FileReference::class)
            ->setMethods(['setPid', 'setOriginalResource'])
            ->disableOriginalConstructor()->getMock();
        $this->objectManager->expects($this->once())->method('get')
            ->with(FileReference::class)
            ->will($this->returnValue($mockFileReference));
        $mockFileReference->expects($this->once())->method('setPid')
            ->with($expectedPageId);

        $this->subject->create($fileId, $configuration);
    }
}
