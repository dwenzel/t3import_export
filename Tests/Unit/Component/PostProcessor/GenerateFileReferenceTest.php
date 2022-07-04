<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
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

use CPSIT\T3importExport\Component\PostProcessor\GenerateFileReference;
use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFileIndexRepositoryTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockFileReferenceFactoryTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockPersistenceManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Class GenerateFileReferenceTest
 */
class GenerateFileReferenceTest extends TestCase
{
    use MockPersistenceManagerTrait,
        MockMessageContainerTrait,
        MockFileReferenceFactoryTrait,
        MockFileIndexRepositoryTrait;

    /**
     * @var GenerateFileReference|MockObject
     */
    protected $subject;


    /**
     * setup subject
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->mockPersistenceManager()
            ->mockFileReferenceFactory()
            ->mockFileIndexRepository()
            ->mockMessageContainer();

        $this->subject = $this->getMockBuilder(GenerateFileReference::class)
            ->setConstructorArgs(
                [
                    $this->persistenceManager,
                    $this->fileReferenceFactory,
                    $this->fileIndexRepository,
                    $this->messageContainer
                ]
            )
            ->setMethods(['logError', 'logNotice'])->getMock();

    }

    /**
     * provides invalid configurations
     * @return array
     */
    public function invalidConfigurationDataProvider(): array
    {
        return [
            'sourceField missing' => [[]],
            'sourceField integer, not string' => [
                ['sourceField' => 4]
            ],
            'sourceField array instead of string' => [
                ['sourceField' => ['bar']]
            ],
            'targetField missing' => [
                ['sourceField' => 'foo']
            ],
            'targetField integer, not string' => [
                ['targetField' => 4]
            ],
            'targetField array instead of string' => [
                ['targetField' => ['bar']]
            ],
            'targetPage is string, can not be interpreted as integer' => [
                [
                    'targetField' => 'foo',
                    'sourceField' => 'bar',
                    'targetPage' => 'baz'
                ]
            ],
            'targetPage is array, not integer' => [
                [
                    'targetField' => 'foo',
                    'sourceField' => 'bar',
                    'targetPage' => ['baz']
                ]
            ]
        ];
    }

    /**
     * @param array $configuration
     * @dataProvider invalidConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $configuration = [
            'sourceField' => 'foo',
            'targetField' => 'bar'
        ];

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testProcessReturnsFalseIfTargetFieldIsNotSettable(): void
    {
        $sourceFieldName = 'foo';
        $targetFieldName = 'bar';
        $properties = [];
        $object = (object)$properties;
        $record = [];
        $configuration = [
            'targetField' => $targetFieldName,
            'sourceField' => $sourceFieldName
        ];

        $this->assertFalse(
            $this->subject->process($configuration, $object, $record)
        );
    }

    public function testProcessReturnsFalseIfContentOfSourceFieldCanNotBeInterpretedAsInteger(): void
    {
        $sourceFieldName = 'foo';
        $sourceFieldValue = 'can not interpreted as integer';
        $targetFieldName = 'bar';
        $properties = [
            $sourceFieldName => $sourceFieldValue
        ];
        $object = (object)$properties;
        $record = [
            $sourceFieldName => $sourceFieldValue
        ];
        $configuration = [
            'targetField' => $targetFieldName,
            'sourceField' => $sourceFieldName
        ];

        $this->assertFalse(
            $this->subject->process($configuration, $object, $record)
        );
    }

    public function testProcessReturnsFalseIfContentOfTargetFieldIsAReferenceToAFileWithSameIdAsSourceField(): void
    {
        $fileId = 3;
        $sourceFieldName = 'foo';
        $sourceFieldValue = $fileId;
        $targetFieldName = 'bar';
        $mockOriginalFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()
            ->setMethods(['getUid'])
            ->getMock();
        $mockOriginalFile->expects($this->once())->method('getUid')
            ->willReturn($fileId);
        $mockOriginalResource = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOriginalFile'])->getMock();
        $mockOriginalResource->expects($this->once())->method('getOriginalFile')
            ->willReturn($mockOriginalFile);
        $targetFieldValue = $this->getMockBuilder(FileReference::class)
            ->setMethods(['getOriginalResource'])->getMock();
        $targetFieldValue->expects($this->once())->method('getOriginalResource')
            ->willReturn($mockOriginalResource);

        $properties = [
            $targetFieldName => $targetFieldValue
        ];
        $object = (object)$properties;
        $record = [
            $sourceFieldName => $sourceFieldValue
        ];
        $configuration = [
            'targetField' => $targetFieldName,
            'sourceField' => $sourceFieldName
        ];

        $this->assertFalse(
            $this->subject->process($configuration, $object, $record)
        );
    }

    public function testProcessRemovesExistingReferenceIfFileIdIsNotTheSameAsTargetField(): void
    {
        $fileId = 3;
        $existingFileId = 5;
        $sourceFieldName = 'foo';
        $sourceFieldValue = $fileId;
        $targetFieldName = 'bar';
        $mockOriginalFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()
            ->setMethods(['getUid'])
            ->getMock();
        $mockOriginalFile->expects($this->once())->method('getUid')
            ->willReturn($existingFileId);
        $mockOriginalResource = $this->getMockBuilder(CoreFileReference::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOriginalFile'])->getMock();
        $mockOriginalResource->expects($this->once())->method('getOriginalFile')
            ->willReturn($mockOriginalFile);
        $targetFieldValue = $this->getMockBuilder(FileReference::class)
            ->setMethods(['getOriginalResource'])->getMock();
        $targetFieldValue->expects($this->once())->method('getOriginalResource')
            ->willReturn($mockOriginalResource);

        $properties = [
            $targetFieldName => $targetFieldValue
        ];
        $object = (object)$properties;
        $record = [
            $sourceFieldName => $sourceFieldValue
        ];
        $configuration = [
            'targetField' => $targetFieldName,
            'sourceField' => $sourceFieldName
        ];

        $this->subject->process($configuration, $object, $record);
    }

    public function testProcessCreatesFileReferenceAndAddsItToTargetField(): void
    {
        $fileId = 3;
        $sourceFieldName = 'foo';
        $targetFieldName = 'bar';
        $mockFileReference = $this->getMockBuilder(FileReference::class)
            ->disableOriginalConstructor()->getMock();

        $properties = [
            $targetFieldName => null
        ];
        $object = (object)$properties;
        $record = [
            $sourceFieldName => $fileId
        ];
        $configuration = [
            'targetField' => $targetFieldName,
            'sourceField' => $sourceFieldName
        ];
        $this->fileReferenceFactory->expects($this->once())
            ->method('create')
            ->with($fileId, $configuration)
            ->willReturn($mockFileReference);

        $this->subject->process($configuration, $object, $record);

        $this->assertAttributeSame(
            $mockFileReference,
            $targetFieldName,
            $object
        );
    }

    public function testProcessReturnsFalseIfFileDoesNotExist(): void
    {
        $fileId = 3;
        $sourceFieldName = 'foo';
        $targetFieldName = 'bar';

        $properties = [
            $targetFieldName => null
        ];
        $object = (object)$properties;
        $record = [
            $sourceFieldName => $fileId
        ];
        $configuration = [
            'targetField' => $targetFieldName,
            'sourceField' => $sourceFieldName
        ];
        $this->fileIndexRepository->expects($this->once())
            ->method('findOneByUid')
            ->willReturn(false);

        $this->fileReferenceFactory->expects($this->never())
            ->method('create');

        $this->assertFalse(
            $this->subject->process($configuration, $object, $record)
        );
    }

    public function testInstanceImplementsLoggingInterface(): void
    {
        $this->assertInstanceOf(
            LoggingInterface::class,
            $this->subject
        );
    }

    public function testGetErrorCodesReturnsClassConstant(): void
    {
        $this->assertSame(
            GenerateFileReference::ERROR_CODES,
            $this->subject->getErrorCodes()
        );
    }

}
