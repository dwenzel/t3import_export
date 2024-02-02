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
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
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

    /**
     * @param object $modifiedObject
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function update($modifiedObject)
    {
    }

    public function findAll()
    {
    }

    /**
     * @return int|void
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function countAll()
    {
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function removeAll()
    {
    }

    /**
     * @param int $uid
     * @return object|void|null
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function findByUid($uid)
    {
    }

    /**
     * @param mixed $identifier
     * @return object|void|null
     */
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

    /**
     * @return QueryInterface|void
     * @noinspection PhpMissingReturnTypeInspection
     */
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

    protected DataTargetRepository $subject;

    /**
     * @var RepositoryInterface|MockObject
     */
    protected RepositoryInterface $objectRepository;

    protected PersistenceManagerInterface $persistanceManager;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->objectRepository = $this->getMockBuilder(MockRepositoryObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPersistenceManager();
        $this->subject = new DataTargetRepository(
            self::TARGET_CLASS,
            $this->objectRepository,
            $this->persistenceManager
        );
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryThrowsExceptionForUnknownClass(): void
    {
        $this->expectException(MissingClassException::class);
        $this->expectExceptionCode(DataTargetRepository::MISSING_CLASS_EXCEPTION_CODE);
        $this->subject = new DataTargetRepository('targetClass', null, $this->persistenceManager);
        $this->subject->getRepository();
    }

    /**
     * @covers ::getRepository
     * @throws MissingClassException
     */
    public function testGetRepositoryReturnsRepositoryIfSet(): void
    {
        $this->assertSame(
            $this->objectRepository,
            $this->subject->getRepository()
        );
    }

    public function testPersistAddsObject(): void
    {
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
        $mockObject = $this->getMockForAbstractClass(AbstractDomainObject::class);

        $this->objectRepository->expects($this->once())
            ->method('update')
            ->with(...[$mockObject]);

        $this->subject->persist($mockObject);
    }

    public function testConstructorSetsTargetClass(): void
    {
        $this->assertSame(
            self::TARGET_CLASS,
            $this->subject->getTargetClass()
        );
    }

    public function testPersistAllPersistsThroughPersistenceManager(): void
    {
        $this->persistenceManager->expects($this->once())
            ->method('persistAll');

        $this->subject->persistAll();
    }
}
