<?php

namespace CPSIT\T3importExport\Tests\PreProcessor;

/**
 * Copyright notice
 * (c) 2017. Dirk Wenzel <wenzel@cps-it.de>
 * All rights reserved
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateFileResourceTest
 */
class GenerateFileResourceTest extends UnitTestCase
{
    /**
     * @var GenerateFileResource |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceFactory;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storageRepository;


    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(GenerateFileResource::class)
            ->setMethods(['logError'])->getMock();

        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasFolder', 'getConfiguration'])->getMock();
        $this->inject(
            $this->subject,
            'storage',
            $this->storage
        );
        $this->resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->setMethods([])->getMock();
        $this->subject->injectResourceFactory($this->resourceFactory);
        $this->storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->setMethods(['findByUid'])->getMock();
        $this->storageRepository->expects($this->any())->method('findByUid')
            ->will($this->returnValue($this->storage));

        $this->subject->injectStorageRepository($this->storageRepository);

    }

    public function configurationDataProvider()
    {
        // $configuration, $expected, $errorId
        return [
            // empty configuration
            [
                [],
                false,
                1497427302,
                null
            ],
            // missing target directory path
            [
                [
                    'storageId' => 'foo'
                ],
                false,
                1497427320,
                null
            ],
            // missing field name
            [
                [
                    'storageId' => 'foo',
                    'targetDirectoryPath' => 'bar'
                ],
                false,
                1497427335,
                null
            ],
            //@todo test for missing storage
/*            [
                [
                    'storageId' => 'foo',
                    'targetDirectoryPath' => 'bar',
                    'fieldName' => 'baz'
                ],
                false,
                1497427346,
                ['foo']
            ],
*/
        ];
    }

    /**
     * @test
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param bool $expected
     * @param string $errorTitle
     * @param string $errorMessage
     */
    public function isConfigurationValidReturnsCorrectValues($configuration, $expected, $expectedErrorId, $expectedErrorArguments)
    {
        $this->subject->expects($this->once())
            ->method('logError')
            ->with($expectedErrorId, $expectedErrorArguments);

        $this->assertSame(
            $expected,
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseForMissingDirectory()
    {
        $configuration = [
            'storageId' => 3,
            'targetDirectoryPath' => 'foo',
            'fieldName' => 'bar'
        ];
        $storageConfiguration = ['basePath' => 'baz'];
        $expectedErrorId = 1497427363;
        $expectedErrorArguments = [$storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'], '/\\')];

        $this->storage->expects($this->once())
            ->method('hasFolder')
            ->with($configuration['targetDirectoryPath'])
            ->will($this->returnValue(false));
        $this->storage->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($storageConfiguration));

        $this->subject->expects($this->once())
            ->method('logError')
            ->with($expectedErrorId, $expectedErrorArguments);

        $this->subject->isConfigurationValid($configuration);
    }


}
