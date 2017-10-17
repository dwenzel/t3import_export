<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceCSV;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class DataSourceCSVTest
 */
class DataSourceCSVTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Persistence\DataSourceCSV
     */
    protected $subject;

    /**
     * @var ResourcePathConfigurationValidator | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $pathValidator;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(DataSourceCSV::class,
            ['dummy', 'getAbsoluteFilePath'], [], '', false);

        $this->pathValidator = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->setMethods(['validate'])->getMock();
        $this->subject->injectResourcePathConfigurationValidator($this->pathValidator);
        vfsStreamWrapper::register();
    }

    /**
     * Get a valid CSV string with headers
     * @return array
     */
    public function validCsvWithHeadersDataProvider()
    {
        $csvString = <<<CSV
"foo","bar","baz"
"fooValue","barValue","bazValue"
CSV;
        $expectedArray = [
            [
                'foo' => 'fooValue',
                'bar' => 'barValue',
                'baz' => 'bazValue'
            ]
        ];
        return [[$csvString, $expectedArray]];
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
    public function isConfigurationValidValidatesPathConfiguration()
    {
        $config = ['foo'];
        $this->pathValidator->expects($this->once())
            ->method('validate')
            ->with($config);
        $this->subject->isConfigurationValid($config);
    }

    /**
     * Data provider for invalid configurations
     */
    public function invalidConfigurationDataProvider()
    {
        return [
            // fields must be string
            [['fields' => 5]],
            // fields must not be empty
            [['fields' => '']],
            // delimiter must be string
            [['delimiter' => 1]],
            // delimiter must be single character
            [['delimiter' => '%%']],
            // delimiter must not be empty
            [['delimiter' => '']],
            // enclosure must be string
            [['enclosure' => 1]],
            // enclosure must be single character
            [['enclosure' => '%%']],
            // enclosure must not be empty
            [['enclosure' => '']],
            // escape must be string
            [['escape' => 1]],
            // escape must be single character
            [['escape' => '%%']],
            // escape must not be empty
            [['escape' => '']],
        ];
    }

    /**
     * @test
     * @dataProvider invalidConfigurationDataProvider
     * @param array $configuration
     */
    public function isConfigurationValidReturnsFalseForInvalidValues(array $configuration)
    {
        $this->pathValidator->expects($this->once())
            ->method('validate')->will($this->returnValue(true));

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $configuration = [
            'file' => 'foo.csv',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => "\\"
        ];
        $this->pathValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     * @dataProvider validCsvWithHeadersDataProvider
     * @param string $csvString
     * @param array $expectedArray
     */
    public function getRecordsReturnsArrayFromValidCsvWithHeaders($csvString, $expectedArray)
    {
        $fileDirectory = 'typo3temp';
        $fileName = 'foo.csv';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath,
        ];

        vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName);
        $mockFile->setContent($csvString);
        vfsStreamWrapper::getRoot()->addChild($mockFile);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($relativePath)
            ->will($this->returnValue(vfsStream::url($relativePath)));

        $this->assertSame(
            $expectedArray,
            $this->subject->getRecords($configuration)
        );
    }

    /**
     * @test
     */
    public function getRecordsReturnsArrayFromValidCsvWithoutHeaders()
    {
        $csvString = <<<CSV
"foo1","bar1","baz1"
"foo2","bar2","baz2"
CSV;

        $fileDirectory = 'typo3temp';
        $fileName = 'foo.csv';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath,
            'fields' => 'boom,bam,bang'
        ];

        $expectedArray = [
            [
                'boom' => 'foo1',
                'bam' => 'bar1',
                'bang' => 'baz1'
            ],
            [
                'boom' => 'foo2',
                'bam' => 'bar2',
                'bang' => 'baz2'
            ]
        ];

        vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName);
        $mockFile->setContent($csvString);
        vfsStreamWrapper::getRoot()->addChild($mockFile);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($relativePath)
            ->will($this->returnValue(vfsStream::url($relativePath)));

        $this->assertSame(
            $expectedArray,
            $this->subject->getRecords($configuration)
        );
    }

    /**
     * Data provider for custom characters
     */
    public function customCharactersDataProvider()
    {
        $fileDirectory = 'typo3temp';
        $fileName = 'foo.csv';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath,
            'fields' => 'boom,bam,bang',
        ];

        $defaultExpected = [
            [
                'boom' => 'foo1',
                'bam' => 'bar1',
                'bang' => 'baz1'
            ]
        ];

        // delimiter
        $delimiterCSV = <<<CSV
"foo1";"bar1";"baz1"
CSV;
        $delimiterConfiguration = $configuration;
        $delimiterConfiguration['delimiter'] = ';';

        // enclosure
        $enclosureCSV = <<<CSV
|foo1|,|bar1|,|baz1|
CSV;
        $enclosureConfiguration = $configuration;
        $enclosureConfiguration['enclosure'] = '|';

        // escape
        $escapeCSV = <<<CSV
"foo1","bar1","|"baz1"
CSV;
        $escapeConfiguration = $configuration;
        $escapeConfiguration['escape'] = '|';
        $escapeExpected = [
            [
                'boom' => 'foo1',
                'bam' => 'bar1',
                'bang' => '|"baz1'
            ]
        ];

        return [
            [$delimiterConfiguration, $delimiterCSV, $defaultExpected, $fileDirectory, $fileName],
            [$enclosureConfiguration, $enclosureCSV, $defaultExpected, $fileDirectory, $fileName],
            [$escapeConfiguration, $escapeCSV, $escapeExpected, $fileDirectory, $fileName]
        ];
    }

    /**
     * @test
     * @dataProvider customCharactersDataProvider
     * @param array $configuration
     * @param string $csvString
     * @param array $expectedArray
     * @param string $fileDirectory
     * @param string, $fileName
     */
    public function getRecordsReturnsArrayFromValidCsvWithCustomCharacters($configuration, $csvString, $expectedArray, $fileDirectory, $fileName)
    {
        $relativePath = $fileDirectory . '/' . $fileName;

        vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName);
        $mockFile->setContent($csvString);
        vfsStreamWrapper::getRoot()->addChild($mockFile);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($relativePath)
            ->will($this->returnValue(vfsStream::url($relativePath)));

        $this->assertSame(
            $expectedArray,
            $this->subject->getRecords($configuration)
        );
    }
}
