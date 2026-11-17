<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceXML;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSourceXML::class)]
class DataSourceXMLTest extends TestCase
{
    /**
     * @var DataSourceXML|MockObject
     */
    protected DataSourceXML $subject;
    protected string $xmlFile = '';

    /**
     * Set up the test subject
     */
    protected function setUp(): void
    {
        $this->subject = new DataSourceXML();
        \org\bovigo\vfs\vfsStreamWrapper::register();
    }

    protected function tearDown(): void
    {
        if ($this->xmlFile !== '' && is_file($this->xmlFile)) {
            unlink($this->xmlFile);
        }
    }

    /**
     * Lets the subject read the given XML from a temporary file
     */
    protected function useXml(string $xml): void
    {
        $this->xmlFile = (string)tempnam(sys_get_temp_dir(), 'xml');
        file_put_contents($this->xmlFile, $xml);
        $file = $this->xmlFile;
        $this->subject = new class () extends DataSourceXML {
            public string $file = '';

            protected function getAbsoluteFilePath($path)
            {
                return $this->file;
            }
        };
        $this->subject->file = $file;
    }

    protected function mockSubject(): void
    {
        $this->subject = $this->getMockBuilder(DataSourceXML::class)
            ->onlyMethods(['getAbsoluteFilePath'])
            ->getMock();
    }

    #[Test]
    public function testetRecordsInitiallyReturnsEmptyArray(): void
    {
        $configuration = [];
        $this->assertSame(
            [],
            $this->subject->getRecords($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseForMissingFile(): void
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfFileIsNotString(): void
    {
        $configuration = [
            'file' => [],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseForInvalidFilePath(): void
    {
        $this->mockSubject();
        $invalidPath = 'fooPath';
        $configuration = [
            'file' => $invalidPath,
        ];

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$invalidPath])
            ->willReturn('');

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $this->mockSubject();
        $fileDirectory = 'typo3temp';
        $fileName = 'foo.xml';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath,
        ];

        // vfsStream setup replaced with direct mock
        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$relativePath])
            ->willReturn($relativePath);

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfFileAndUrlAreSet(): void
    {
        $configuration = [
            'file' => 'foo',
            'url' => 'bar',
        ];

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfUrlIsNotString(): void
    {
        $configuration = [
            'url' => [],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfUrlIsInvalid(): void
    {
        $configuration = [
            'url' => 'foo',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsTrueIfUrlIsValid(): void
    {
        $configuration = [
            'url' => 'http://typo3.org',
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfExpressionIsNotString(): void
    {
        $configuration = [
            'url' => 'http://typo3.org',
            'expression' => 5,
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    protected const string XML = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <courses>
          <course id="1">
            <title>First</title>
            <description><![CDATA[<p>Text</p>]]></description>
            <tags><tag>a</tag><tag>b</tag></tags>
          </course>
          <course id="2">
            <title>Second</title>
            <description/>
            <tags><tag>c</tag></tags>
          </course>
        </courses>
        XML;

    #[Test]
    public function getRecordsConvertsMatchingElementsToArrays(): void
    {
        $this->useXml(self::XML);

        $records = $this->subject->getRecords(['file' => 'EXT:foo/courses.xml', 'expression' => '//course']);

        self::assertSame(
            [
                0 => [
                    '@attributes' => ['id' => '1'],
                    'title' => 'First',
                    'description' => '<p>Text</p>',
                    'tags' => ['tag' => ['a', 'b']],
                ],
                1 => [
                    '@attributes' => ['id' => '2'],
                    'title' => 'Second',
                    'description' => [],
                    'tags' => ['tag' => 'c'],
                ],
            ],
            $records
        );
    }

    #[Test]
    public function getRecordsUsesXpathExpression(): void
    {
        $this->useXml(self::XML);

        $records = $this->subject->getRecords(['file' => 'EXT:foo/courses.xml', 'expression' => '//course[@id="2"]/title']);

        self::assertSame([0 => [0 => 'Second']], $records);
    }

    public static function noRecordsDataProvider(): iterable
    {
        yield 'no expression' => [self::XML, []];
        yield 'expression without match' => [self::XML, ['expression' => '//event']];
        yield 'empty file' => ['', ['expression' => '//course']];
    }

    #[Test]
    #[DataProvider('noRecordsDataProvider')]
    public function getRecordsReturnsEmptyArray(string $xml, array $configuration): void
    {
        $this->useXml($xml);

        self::assertSame([], $this->subject->getRecords($configuration + ['file' => 'EXT:foo/courses.xml']));
    }

    #[Test]
    public function getRecordsThrowsExceptionForMalformedXml(): void
    {
        $this->useXml('<courses><course></courses>');

        $this->expectException(\Exception::class);

        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $this->subject->getRecords(['file' => 'EXT:foo/courses.xml', 'expression' => '//course']);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }
    }
}
