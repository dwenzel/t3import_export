<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceXML;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
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
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @throws vfsStreamException
     */
    public function setUp()
    {
        $this->subject = new DataSourceXML();
        vfsStreamWrapper::register();
    }

    protected function mockSubject(): void
    {
        $this->subject = $this->getMockBuilder(DataSourceXML::class)
            ->setMethods(['getAbsoluteFilePath'])
            ->getMock();
    }

    /**
     */
    public function testetRecordsInitiallyReturnsEmptyArray(): void
    {
        $configuration = [];
        $this->assertSame(
            [],
            $this->subject->getRecords($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsFalseForMissingFile(): void
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsFalseIfFileIsNotString(): void
    {
        $configuration = [
            'file' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsFalseForInvalidFilePath(): void
    {
        $this->mockSubject();
        $invalidPath = 'fooPath';
        $configuration = [
            'file' => $invalidPath
        ];

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$invalidPath])
            ->willReturn('');

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $this->mockSubject();
        $fileDirectory = 'typo3temp';
        $fileName = 'foo.xml';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath
        ];

        $root = vfsStream::setup($fileDirectory);
        vfsStream::newFile($fileName)->at($root);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$relativePath])
            ->willReturn(vfsStream::url($relativePath));

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsFalseIfFileAndUrlAreSet(): void
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
     */
    public function testIsConfigurationValidReturnsFalseIfUrlIsNotString(): void
    {
        $configuration = [
            'url' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsFalseIfUrlIsInvalid(): void
    {
        $configuration = [
            'url' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsTrueIfUrlIsValid(): void
    {
        $configuration = [
            'url' => 'http://typo3.org'
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     */
    public function testIsConfigurationValidReturnsFalseIfExpressionIsNotString(): void
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
