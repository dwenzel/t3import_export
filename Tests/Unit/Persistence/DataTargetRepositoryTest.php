<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\Persistence\DataTargetRepository;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockPersistenceManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

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

class MockRepositoryObjectRepository extends Repository
{
    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function add($object)
    {
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function remove($object)
    {
    }

    /**  */
    public function update($modifiedObject)
    {
    }

    public function findAll()
    {
    }

    public function countAll()
    {
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function removeAll()
    {
    }

    public function findByUid($uid)
    {
    }

    public function findByIdentifier($identifier)
    {
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setDefaultOrderings(array $defaultOrderings)
    {
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setDefaultQuerySettings(QuerySettingsInterface $defaultQuerySettings)
    {
    }

    public function createQuery()
    {
    }
}

/**
 * Class DataTargetRepositoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\DataTargetRepository
 */
class DataTargetRepositoryTest extends TestCase
{
    use MockObjectManagerTrait,
        MockPersistenceManagerTrait;

    protected const TARGET_CLASS = 'oof';

    /**
     * @var DataTargetRepository
     */
    protected $subject;

    /**
     * @var RepositoryInterface|MockObject
     */
    protected RepositoryInterface $objectRepository;

    protected PersistenceManagerInterface $persistanceManager;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = new DataTargetRepository(self::TARGET_CLASS);
        $this->objectRepository = $this->getMockBuilder(MockRepositoryObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockObjectManager();
        $this->mockPersistenceManager();
    }

    /**
     * @covers ::injectObjectManager
     */
    public function testInjectObjectManagerForObjectSetsObjectManager(): void
    {
        $this->assertAttributeSame(
            $this->objectManager,
            'objectManager',
            $this->subject
        );
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryThrowsExceptionForUnknownClass(): void
    {
        $this->expectException(MissingClassException::class);
        $this->expectExceptionCode(DataTargetRepository::MISSING_CLASS_EXCEPTION_CODE);
        $this->subject = new DataTargetRepository('targetClass');
        $this->subject->getRepository();
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryReturnsRepositoryIfSet(): void
    {
        $this->subject = new DataTargetRepository(MockModelObject::class, $this->objectRepository);
        $this->mockObjectManager();
        $this->objectManager->method('get')->willReturn($this->objectRepository);
        $this->assertSame(
            $this->objectRepository,
            $this->subject->getRepository()
        );
    }

    /**
     * @covers ::getRepository
     * @throws Exception
     */
    public function testGetRepositoryCreatesRepositoryFromClassName(): void
    {
        $this->subject = new DataTargetRepository(MockModelObject::class);
        $this->mockObjectManager();

        $repositoryClass = str_replace('Model', 'Repository', MockModelObject::class) . 'Repository';

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(...[$repositoryClass])
            ->willReturn($this->objectRepository);

        $this->assertSame(
            $this->objectRepository,
            $this->subject->getRepository()
        );
    }

    /**
     * @covers ::persist
     */
    public function testPersistAddsObject(): void
    {
        $this->subject = new DataTargetRepository(self::TARGET_CLASS, $this->objectRepository);
        $this->mockPersistenceManager();

        $mockObject = $this->getMockForAbstractClass(DomainObjectInterface::class);
        $this->persistenceManager->expects($this->once())
            ->method('isNewObject')
            ->with(...[$mockObject])
            ->willReturn(true);
        $this->objectRepository->expects($this->once())
            ->method('add')
            ->with(...[$mockObject]);

        $this->subject->persist($mockObject, []);
    }


    /**
     * @covers ::persist
     */
    public function testPersistUpdatesObject(): void
    {
        $this->subject = new DataTargetRepository(self::TARGET_CLASS, $this->objectRepository);
        $this->mockPersistenceManager();

        $mockObject = $this->getMockForAbstractClass(AbstractDomainObject::class);

        $this->objectRepository->expects($this->once())
            ->method('update')
            ->with(...[$mockObject]);

        $this->subject->persist($mockObject);
    }

    public function testConstructorSetsTargetClass(): void
    {
        $targetClass = 'foo';
        $subject = new DataTargetRepository($targetClass);
        $this->assertSame(
            $targetClass,
            $subject->getTargetClass()
        );
    }

    public function testInjectPersistenceManagerSetsPersistenceManager(): void
    {
        $this->assertAttributeSame(
            $this->persistenceManager,
            'persistenceManager',
            $this->subject
        );
    }

    public function testPersistAllPersistsThroughPersistenceManager(): void
    {
        $this->persistenceManager->expects($this->once())
            ->method('persistAll');

        $this->subject->persistAll();
    }
}
