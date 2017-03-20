<?php
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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Class ResourcePathConfigurationValidatorTest
 */
class ResourcePathConfigurationValidatorTest extends UnitTestCase
{
    /**
     * @var ResourcePathConfigurationValidator | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * set up the subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->setMethods(['dummy', 'getAbsoluteFilePath'])
            ->getMock();
        vfsStreamWrapper::register();
    }


    /**
     * @test
     */
    public function validateReturnsFalseForMissingFile()
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->validate($configuration)
        );
    }

    /**
     * @test
     */
    public function validateReturnsFalseIfFileIsNotString()
    {
        $configuration = [
            'file' => []
        ];
        $this->assertFalse(
            $this->subject->validate($configuration)
        );
    }

    /**
     * @test
     */
    public function validateReturnsFalseForInvalidFilePath()
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
            $this->subject->validate($configuration)
        );
    }

    /**
     * @test
     */
    public function validateReturnsTrueForValidConfiguration()
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
            $this->subject->validate($configuration)
        );
    }

    /**
     * @test
     */
    public function validateReturnsFalseIfFileAndUrlAreSet()
    {
        $configuration = [
            'file' => 'foo',
            'url' => 'bar'
        ];

        $this->assertFalse(
            $this->subject->validate($configuration)
        );
    }

    /**
     * @test
     */
    public function validateReturnsFalseIfUrlIsNotString()
    {
        $configuration = [
            'url' => []
        ];
        $this->assertFalse(
            $this->subject->validate($configuration)
        );

    }

    /**
     * @test
     */
    public function validateReturnsFalseIfUrlIsInvalid()
    {
        $configuration = [
            'url' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->validate($configuration)
        );
    }

    /**
     * @test
     */
    public function validateReturnsTrueIfUrlIsValid()
    {
        $configuration = [
            'url' => 'http://typo3.org'
        ];
        $this->assertTrue(
            $this->subject->validate($configuration)
        );
    }
}