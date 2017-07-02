<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

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

use CPSIT\T3importExport\Component\PreProcessor\GenerateUploadFile;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class GenerateUploadFileTest
 */
class GenerateUploadFileTest extends UnitTestCase
{
    /**
     * @var GenerateUploadFile |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceStorage;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storageRepository;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(GenerateUploadFile::class)
            ->setMethods(['getAbsoluteFilePath'])->getMock();

        $this->resourceStorage = $this->getMockBuilder( ResourceStorage::class)->disableOriginalConstructor()
            ->setMethods(['getConfiguration'])->getMock();

        $this->inject(
            $this->subject,
            'resourceStorage',
            $this->resourceStorage
        );
        
    }

    /**
     * Provides dependenccies for injection tests
     */
    public function dependenciesDataProvider()
    {
        return [
            [StorageRepository::class, 'storageRepository']
        ];
    }

    /**
     * @test
     * @dataProvider dependenciesDataProvider
     * @param string $class Class name of the dependency to inject
     * @param string $propertyName The property holding the dependency
     */
    public function dependenciesCanBeInjected($class, $propertyName)
    {
        $mockDependency = $this->getMockBuilder($class)->disableOriginalConstructor()
            ->getMock();

        $methodName = 'inject' . ucfirst($propertyName);
        $this->subject->{$methodName}($mockDependency);

        $this->assertAttributeSame(
            $mockDependency,
            $propertyName,
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getFileInitiallyReturnsEmptyString()
    {
        $sourceFilePath = 'bang';
        $storageConfiguration = [
            'basePath' => ''
        ];
        $configuration = [
            'targetDirectoryPath' => 'foo'
        ];

        $this->resourceStorage->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($storageConfiguration));

        $this->assertSame(
            '',
            $this->subject->getFile($configuration, $sourceFilePath)
        );
    }
}
