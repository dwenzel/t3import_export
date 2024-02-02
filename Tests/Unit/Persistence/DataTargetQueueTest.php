<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Domain\Repository\QueueRepository;
use CPSIT\T3importExport\Persistence\DataTargetQueue;
use CPSIT\T3importExport\Tests\Unit\Traits\MockPersistenceManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class DataTargetQueueTest extends TestCase
{
    use MockPersistenceManagerTrait;

    protected DataTargetQueue $subject;
    /***
     * @var QueueItemRepository
     */
    protected QueueItemRepository $repository;

    protected const VALID_CONFIGURATION = [
        DataTargetQueue::KEY_IDENTIFIER => 'import.foo'
    ];
    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(QueueItemRepository::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->subject = new DataTargetQueue($this->repository);
    }

    public function inValidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                []
            ],
            'identifier not set' => [
                ['foo' => 'bar']
            ],
            'identifier must begin with import. or export.' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'foo']
            ],
            'allowUpdate must not be array' => [
                [DataTargetQueue::KEY_ALLOW_UPDATE => []]
            ],
            'allowUpdate must not be float' => [
                [DataTargetQueue::KEY_ALLOW_UPDATE => 3.1]
            ],
            'allowUpdate must not be integer' => [
                [DataTargetQueue::KEY_ALLOW_UPDATE => 3.1]
            ]
        ];
    }

    /**
     * @param array $configuration
     * @dataProvider inValidConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testPersistReturnsFalseIfObjectIsNotArray(): void
    {
        $object = $this->getMockForAbstractClass(DomainObjectInterface::class);
        $configuration = self::VALID_CONFIGURATION;
        self::assertFalse(
            $this->subject->persist($object, $configuration)
        );
    }

    public function testPersistAddsNewObject(): never
    {
        $this->markTestIncomplete('to be done');
    }

    public function testPersistUpdatesObjectsIfAllowedByConfiguration(): never
    {
        $this->markTestIncomplete('to be done');
    }

    public function testPersistReturnsFalseIfRepositoryRejectsObject(): never
    {
        // repository throws InvalidArgumentException
        $this->markTestIncomplete('to be done');
    }
}
