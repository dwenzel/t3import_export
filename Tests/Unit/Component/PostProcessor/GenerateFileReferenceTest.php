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

use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use CPSIT\T3importExport\Component\PostProcessor\GenerateFileReference;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Class GenerateFileReferenceTest
 */
class GenerateFileReferenceTest extends UnitTestCase
{
    /**
     * @var GenerateFileReference|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var FileReferenceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileReferenceFactory;

    /**
     * @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistenceManager;

    /**
     * @var FileIndexRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileIndexRepository;


    /**
     * setup subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(GenerateFileReference::class)
            ->setMethods(['logError', 'logNotice'])->getMock();
        $this->fileReferenceFactory = $this->getMockBuilder(FileReferenceFactory::class)
            ->setMethods(['create'])->getMock();
        $this->subject->injectFileReferenceFactory($this->fileReferenceFactory);
        $this->persistenceManager = $this->getMockBuilder(PersistenceManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['remove'])
            ->getMockForAbstractClass();
        $this->subject->injectPersistenceManager($this->persistenceManager);
        $this->fileIndexRepository = $this->getMockBuilder(FileIndexRepository::class)
            ->disableOriginalConstructor()->setMethods(['findOneByUid'])
            ->getMock();
        $this->subject->injectFileIndexRepository($this->fileIndexRepository);

    }

    /**
     * @test
     */
    public function persistenceManagerCanBeInjected() {
        /** @var PersistenceManager|\PHPUnit_Framework_MockObject_MockObject $persistenceManager */
        $persistenceManager = $this->getMockBuilder(PersistenceManager::class)->disableOriginalConstructor()
            ->getMock();
        $this->subject->injectPersistenceManager($persistenceManager);
        $this->assertAttributeSame(
            $persistenceManager, 'persistenceManager', $this->subject
        );
    }

    /**
     * provides invalid configurations
     * @return array
     */
    public function invalidConfigurationDataProvider()
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
     * @test
     * @param array $configuration
     * @dataProvider invalidConfigurationDataProvider
     */
    public function isConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration) {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration() {
        $configuration = [
            'sourceField' => 'foo',
            'targetField' => 'bar'
        ];

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function processReturnsFalseIfTargetFieldIsNotSettable() {
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

    /**
     * @test
     */
    public function processReturnsFalseIfContentOfSourceFieldCanNotBeInterpretedAsInteger()
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

    /**
     * @test
     */
    public function processReturnsFalseIfContentOfTargetFieldIsAReferenceToAFileWithSameIdAsSourceField()
    {
        $fileId = 3;
        $sourceFieldName = 'foo';
        $sourceFieldValue = $fileId;
        $targetFieldName = 'bar';
        $mockOriginalFile = $this->getMockBuilder(File::class)->disableOriginalConstructor()
            ->setMethods(['getUid'])
            ->getMock();
        $mockOriginalFile->expects($this->once())->method('getUid')
            ->will($this->returnValue($fileId));
        $mockOriginalResource = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\FileReference::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOriginalFile'])->getMock();
        $mockOriginalResource->expects($this->once())->method('getOriginalFile')
            ->will($this->returnValue($mockOriginalFile));
        $targetFieldValue = $this->getMockBuilder(FileReference::class)
            ->setMethods(['getOriginalResource'])->getMock();
        $targetFieldValue->expects($this->once())->method('getOriginalResource')
            ->will($this->returnValue($mockOriginalResource));

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

    /**
     * @test
     */
    public function processRemovesExistingReferenceIfFileIdIsNotTheSameAsTargetField()
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
            ->will($this->returnValue($existingFileId));
        $mockOriginalResource = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\FileReference::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOriginalFile'])->getMock();
        $mockOriginalResource->expects($this->once())->method('getOriginalFile')
            ->will($this->returnValue($mockOriginalFile));
        $targetFieldValue = $this->getMockBuilder(FileReference::class)
            ->setMethods(['getOriginalResource'])->getMock();
        $targetFieldValue->expects($this->once())->method('getOriginalResource')
            ->will($this->returnValue($mockOriginalResource));

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

    /**
     * @test
     */
    public function processCreatesFileReferenceAndAddsItToTargetField()
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
            ->will($this->returnValue($mockFileReference));

        $this->subject->process($configuration, $object, $record);

        $this->assertAttributeSame(
            $mockFileReference,
            $targetFieldName,
            $object
        );
    }

    /**
     * @test
     */
    public function processReturnsFalseIfFileDoesNotExist() {
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
            ->will($this->returnValue(false));

        $this->fileReferenceFactory->expects($this->never())
            ->method('create');

        $this->assertFalse(
            $this->subject->process($configuration, $object, $record)
        );
    }

    /**
     * @test
     */
    public function instanceImplementsLoggingInterface() {
        $this->assertInstanceOf(
            LoggingInterface::class,
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getErrorCodesReturnsClassConstant() {
        $this->assertSame(
            GenerateFileReference::ERROR_CODES,
            $this->subject->getErrorCodes()
        );
    }
}
