<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\T3importExport\Persistence\DataSourceXML;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Class DataSourceXMLTest
 */
class DataSourceXMLTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Persistence\DataSourceXML
     */
    protected $subject;

    /**
     *
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(DataSourceXML::class,
            ['dummy', 'getAbsoluteFilePath'], [], '', false);
        vfsStreamWrapper::register();
    }

    /**
     * @test
     */
    public function getRecordsInitiallyReturnsEmptyArray()
    {
        $configuration = [];
        $this->assertSame(
            [],
            $this->subject->getRecords($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseForMissingFile()
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfFileIsNotString()
    {
        $configuration = [
            'file' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfForInvalidFilePath()
    {
        $invalidPath = 'fooPath';
        $configuration = [
            'file' => $invalidPath
        ];


        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($invalidPath)
            ->will($this->returnValue(''));

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $fileDirectory = 'typo3temp';
        $fileName = 'foo.xml';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath
        ];

        $root = vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName)->at($root);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($relativePath)
            ->will($this->returnValue(vfsStream::url($relativePath)));

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfFileAndUrlAreSet()
    {
        $configuration = [
            'file' => 'foo',
            'url' => 'bar'
        ];

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfUrlIsNotString()
    {
        $configuration = [
            'url' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfUrlIsInvalid()
    {
        $configuration = [
            'url' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueIfUrlIsValid()
    {
        $configuration = [
            'url' => 'http://typo3.org'
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfExpressionIsNotString()
    {
        $configuration = [
            'url' => 'http://typo3.org',
            'expression' => 5
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }
}
