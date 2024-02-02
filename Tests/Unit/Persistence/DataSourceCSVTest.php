<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceCSV;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class DataSourceCSVTest
 */
class DataSourceCSVTest extends TestCase
{

    /**
     * @var DataSourceCSV|MockObject
     */
    protected $subject;

    /**
     * @var ResourcePathConfigurationValidator|MockObject
     */
    protected $configurationValidator;

    /**
     * set up subject
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @throws vfsStreamException
     */
    protected function setUp(): void
    {
        $this->configurationValidator = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->setMethods(['isValid'])->getMock();
        $this->subject = $this->getMockBuilder(DataSourceCSV::class)
            ->setConstructorArgs([$this->configurationValidator])
            ->setMethods(['getAbsoluteFilePath'])
            ->getMock();

        vfsStreamWrapper::register();
    }

    /**
     * Get a valid CSV string with headers
     * @return array
     */
    public function validCsvWithHeadersDataProvider(): array
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


    public function testGetRecordsInitiallyReturnsEmptyArray(): void
    {
        $configuration = [];

        $this->assertSame(
            [],
            $this->subject->getRecords($configuration)
        );
    }

    public function testIsConfigurationValidValidatesPathConfiguration(): void
    {
        $config = ['foo'];
        $this->configurationValidator->expects($this->once())
            ->method('isValid')
            ->with($config);
        $this->subject->isConfigurationValid($config);
    }

    /**
     * Data provider for invalid configurations
     */
    public function invalidConfigurationDataProvider(): array
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
     * @dataProvider invalidConfigurationDataProvider
     * @param array $configuration
     */
    public function testIsConfigurationValidReturnsFalseForInvalidValues(array $configuration): void
    {
        $this->configurationValidator->expects($this->once())
            ->method('isValid')->willReturn(true);

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $configuration = [
            'file' => 'foo.csv',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => "\\"
        ];
        $this->configurationValidator->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @dataProvider validCsvWithHeadersDataProvider
     * @param string $csvString
     * @param array $expectedArray
     */
    public function testGetRecordsReturnsArrayFromValidCsvWithHeaders(string $csvString, array $expectedArray): void
    {
        [$relativePath, $configuration] = $this->mockValidCsvFileWithHeaders($csvString);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$relativePath])
            ->willReturn(vfsStream::url($relativePath));

        $this->assertSame(
            $expectedArray,
            $this->subject->getRecords($configuration)
        );
    }

    /**
     * @param string $csvString
     * @return array
     */
    protected function mockValidCsvFileWithHeaders(string $csvString): array
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
        return [$relativePath, $configuration];
    }

    public function tesGetRecordsReturnsArrayFromValidCsvWithoutHeaders(): void
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
            ->with(...[$relativePath])
            ->willReturn(vfsStream::url($relativePath));

        $this->assertSame(
            $expectedArray,
            $this->subject->getRecords($configuration)
        );
    }

    /**
     * Data provider for custom characters
     */
    public function customCharactersDataProvider(): array
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
     * @dataProvider customCharactersDataProvider
     * @param array $configuration
     * @param string $csvString
     * @param array $expectedArray
     * @param string $fileDirectory
     * @param string $fileName
     */
    public function testGetRecordsReturnsArrayFromValidCsvWithCustomCharacters(
        array $configuration,
        string $csvString,
        array $expectedArray,
        string $fileDirectory,
        string $fileName
    ): void
    {
        $relativePath = $fileDirectory . '/' . $fileName;

        vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName);
        $mockFile->setContent($csvString);
        vfsStreamWrapper::getRoot()->addChild($mockFile);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$relativePath])
            ->willReturn(vfsStream::url($relativePath));

        $this->assertSame(
            $expectedArray,
            $this->subject->getRecords($configuration)
        );
    }
}
