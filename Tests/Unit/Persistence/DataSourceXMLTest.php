<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceXML;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class DataSourceXMLTest
 */
class DataSourceXMLTest extends TestCase
{
    /**
     * @var DataSourceXML|MockObject
     */
    protected DataSourceXML $subject;

    /**
     * Set up the test subject
     */
    protected function setUp(): void
    {
        $this->subject = new DataSourceXML();
        \org\bovigo\vfs\vfsStreamWrapper::register();
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
}
