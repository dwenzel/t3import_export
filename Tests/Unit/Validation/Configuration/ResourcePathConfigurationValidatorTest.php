<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Validation\Configuration;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ResourcePathConfigurationValidatorTest
 */
class ResourcePathConfigurationValidatorTest extends TestCase
{
    /**
     * @var ResourcePathConfigurationValidator |MockObject
     */
    protected $subject;

    /**
     * set up the subject
     * @throws \org\bovigo\vfs\vfsStreamException
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->onlyMethods(['getAbsoluteFilePath'])
            ->getMock();
        vfsStreamWrapper::register();
    }
    #[Test]
    public function validateReturnsFalseForMissingFile(): void
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsFalseIfFileIsNotString(): void
    {
        $configuration = [
            'file' => [],
        ];
        $this->assertFalse(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsFalseForInvalidFilePath(): void
    {
        $invalidPath = 'fooPath';
        $configuration = [
            'file' => $invalidPath,
        ];

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($invalidPath)
            ->willReturn('');

        $this->assertFalse(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsTrueForValidConfiguration(): void
    {
        $fileDirectory = 'typo3temp';
        $fileName = 'foo.xml';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath,
        ];

        $root = vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName)->at($root);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($relativePath)
            ->willReturn(vfsStream::url($relativePath));

        $this->assertTrue(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsFalseIfFileAndUrlAreSet(): void
    {
        $configuration = [
            'file' => 'foo',
            'url' => 'bar',
        ];

        $this->assertFalse(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsFalseIfUrlIsNotString(): void
    {
        $configuration = [
            'url' => [],
        ];
        $this->assertFalse(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsFalseIfUrlIsInvalid(): void
    {
        $configuration = [
            'url' => 'foo',
        ];
        $this->assertFalse(
            $this->subject->isValid($configuration)
        );
    }

    #[Test]
    public function validateReturnsTrueIfUrlIsValid(): void
    {
        $configuration = [
            'url' => "https://typo3.org",
        ];
        $this->assertTrue(
            $this->subject->isValid($configuration)
        );
    }
}
