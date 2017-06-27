<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataTargetRepository;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
 ***************************************************************/

/**
 * Class MockModelObject
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 */
class MockModelObject
{
}

/**
 * Class MockRepositoryObjectRepository
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 */
class MockRepositoryObjectRepository
{
}
/**
 * Class DataTargetRepositoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\DataTargetRepository
 */
class DataTargetRepositoryTest extends UnitTestCase
{

    /**
     * @var DataTargetRepository
     */
    protected $subject;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            DataTargetRepository::class, ['dummy'], [], '', false
        );
    }

    /**
     * @test
     * @covers ::injectObjectManager
     */
    public function injectObjectManagerForObjectSetsObjectManager()
    {
        /** @var ObjectManager $mockObjectManager */
        $mockObjectManager = $this->getMock(ObjectManager::class,
            [], [], '', false);

        $this->subject->injectObjectManager($mockObjectManager);

        $this->assertSame(
            $mockObjectManager,
            $this->subject->_get('objectManager')
        );
    }

    /**
     * @test
     * @covers ::getRepository
     * @expectedException \TYPO3\CMS\Extbase\Object\UnknownClassException
     */
    public function getRepositoryThrowsExceptionForUnknownClass()
    {
        $this->subject->_set('targetClass', 'FooClassName');
        $this->subject->_call('getRepository');
    }

    /**
     * @test
     * @covers ::getRepository
     */
    public function getRepositoryReturnsRepositoryIfSet()
    {
        $mockRepository = $this->getAccessibleMockForAbstractClass(
            Repository::class, [], '', false
        );
        $this->subject->_set('repository', $mockRepository);
        $this->assertSame(
            $mockRepository,
            $this->subject->_call('getRepository')
        );
    }

    /**
     * @test
     * @covers ::getRepository
     */
    public function getRepositoryCreatesRepositoryFromClassName()
    {
        $mockRepository = $this->getAccessibleMockForAbstractClass(
            MockRepositoryObjectRepository::class, [], '', false
        );
        $this->subject->_set(
            'targetClass',
            MockModelObject::class
        );
        $mockObjectManager = $this->getMock(ObjectManager::class,
            ['get'], [], '', false);
        $repositoryClass = str_replace('Model', 'Repository', MockModelObject::class) . 'Repository';

        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($repositoryClass)
            ->will($this->returnValue($mockRepository));

        $this->assertSame(
            $mockRepository,
            $this->subject->_call('getRepository')
        );
    }

    /**
     * @test
     * @covers ::persist
     */
    public function persistAddsObject()
    {
        $this->subject = $this->getAccessibleMock(
            DataTargetRepository::class, ['getRepository'], [], '', false
        );

        $mockPersistenceManager = $this->getMockForAbstractClass(
            PersistenceManagerInterface::class
        );
        $mockObject = $this->getMock(
            DomainObjectInterface::class
        );
        $mockRepository = $this->getAccessibleMock(
            Repository::class, ['add'], [], '', false
        );
        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->subject->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($mockRepository));
        $mockPersistenceManager->expects($this->once())
            ->method('isNewObject')
            ->with($mockObject)
            ->will($this->returnValue(true));
        $mockRepository->expects($this->once())
            ->method('add')
            ->with($mockObject);

        $this->subject->persist($mockObject, []);
    }


    /**
     * @test
     * @covers ::persist
     */
    public function persistUpdatesObject()
    {
        $this->subject = $this->getAccessibleMock(
            DataTargetRepository::class, ['getRepository'], [], '', false
        );

        $mockPersistenceManager = $this->getMockForAbstractClass(
            PersistenceManagerInterface::class
        );
        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $mockRepository = $this->getAccessibleMock(
            Repository::class, ['update'], [], '', false
        );
        $this->subject->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($mockRepository));

        $mockObject = $this->getMock(
            AbstractDomainObject::class, ['getUid']
        );
        $this->subject->_set('repository', $mockRepository);
        $mockRepository->expects($this->once())
            ->method('update')
            ->with($mockObject);

        $this->subject->persist($mockObject);
    }

    /**
     * @test
     */
    public function constructorSetsTargetClass()
    {
        $targetClass = 'foo';
        $subject = new DataTargetRepository($targetClass);
        $this->assertSame(
            $targetClass,
            $subject->getTargetClass()
        );
    }

    /**
     * @test
     */
    public function injectPersistenceManagerSetsPersistenceManager()
    {
        $mockPersistenceManager = $this->getMockForAbstractClass(
            PersistenceManagerInterface::class
        );

        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->assertAttributeSame(
            $mockPersistenceManager,
            'persistenceManager',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function persistAllPersistsThroughPersistenceManager()
    {
        /** @var PersistenceManagerInterface $mockPersistenceManager */
        $mockPersistenceManager = $this->getMockForAbstractClass(
            PersistenceManagerInterface::class
        );
        $this->subject->injectPersistenceManager($mockPersistenceManager);

        $mockPersistenceManager->expects($this->once())
            ->method('persistAll');

        $this->subject->persistAll();
    }
}
