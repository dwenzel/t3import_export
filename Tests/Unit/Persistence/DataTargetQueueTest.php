<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Domain\Repository\QueueRepository;
use CPSIT\T3importExport\Persistence\DataTargetQueue;
use PHPUnit\Framework\Attributes\DataProvider;
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
    protected DataTargetQueue $subject;
    /***
     * @var QueueItemRepository
     */
    protected QueueItemRepository $repository;

    protected const VALID_CONFIGURATION = [
        DataTargetQueue::KEY_IDENTIFIER => 'import.foo',
    ];
    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(QueueItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new DataTargetQueue($this->repository);
    }

    public static function inValidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                [],
            ],
            'identifier not set' => [
                ['foo' => 'bar'],
            ],
            'identifier must begin with import. or export.' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'foo'],
            ],
            'allowUpdate must not be array' => [
                [DataTargetQueue::KEY_ALLOW_UPDATE => []],
            ],
            'allowUpdate must not be float' => [
                [DataTargetQueue::KEY_ALLOW_UPDATE => 3.1],
            ],
            'allowUpdate must not be integer' => [
                [DataTargetQueue::KEY_ALLOW_UPDATE => 3.1],
            ],
        ];
    }

    #[DataProvider('inValidConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testPersistReturnsFalseIfObjectIsNotArray(): void
    {
        $object = $this->createMock(DomainObjectInterface::class);
        $configuration = self::VALID_CONFIGURATION;
        self::assertFalse(
            $this->subject->persist($object, $configuration)
        );
    }

    public function testPersistAddsNewObject(): void
    {
        $objectData = [
            'data' => 'test data content',
            'some_field' => 'value',
        ];
        $configuration = [
            DataTargetQueue::KEY_IDENTIFIER => 'import.test',
        ];
        
        // The repository should check if the object is new
        $this->repository->expects($this->once())
            ->method('isNew')
            ->with($this->callback(function ($item) use ($objectData, $configuration) {
                // Verify that identifier and checksum are set correctly
                return $item['identifier'] === $configuration[DataTargetQueue::KEY_IDENTIFIER]
                    && $item['checksum'] === sha1($objectData['data'] . $configuration[DataTargetQueue::KEY_IDENTIFIER])
                    && $item['data'] === $objectData['data']
                    && $item['some_field'] === $objectData['some_field'];
            }))
            ->willReturn(true);

        // Repository should call add method for new objects
        $this->repository->expects($this->once())
            ->method('add')
            ->willReturn(true);

        $result = $this->subject->persist($objectData, $configuration);
        
        self::assertTrue($result);
    }

    public function testPersistUpdatesObjectsIfAllowedByConfiguration(): void
    {
        $objectData = [
            'identifier' => 'import.test',
            'checksum' => 'existing_checksum',
            'data' => 'updated data content',
            'some_field' => 'updated_value',
        ];
        $configuration = [
            DataTargetQueue::KEY_IDENTIFIER => 'import.test',
            DataTargetQueue::KEY_ALLOW_UPDATE => '1', // Enable updates
        ];
        
        // The repository should check if the object is new (it's not)
        $this->repository->expects($this->once())
            ->method('isNew')
            ->with($objectData)
            ->willReturn(false);

        // Repository should call update method since allowUpdate is enabled
        $this->repository->expects($this->once())
            ->method('update')
            ->with($objectData)
            ->willReturn(true);

        $result = $this->subject->persist($objectData, $configuration);
        
        self::assertTrue($result);
    }

    public function testPersistReturnsFalseIfRepositoryRejectsObject(): void
    {
        $objectData = [
            'data' => 'test data content',
            'some_field' => 'value',
        ];
        $configuration = [
            DataTargetQueue::KEY_IDENTIFIER => 'import.test',
        ];
        
        // The repository should check if the object is new
        $this->repository->expects($this->once())
            ->method('isNew')
            ->willReturn(true);

        // Repository throws InvalidArgumentException when trying to add
        $this->repository->expects($this->once())
            ->method('add')
            ->willThrowException(new InvalidArgumentException('Record is invalid', 1644911541));

        $result = $this->subject->persist($objectData, $configuration);
        
        self::assertFalse($result);
    }
}
