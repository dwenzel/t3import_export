<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Factory\PostProcessorFactory;
use CPSIT\T3importExport\Component\PostProcessor\AbstractPostProcessor;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class DummyValidPostProcessor
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidPostProcessor extends AbstractPostProcessor implements PostProcessorInterface
{
    /**
     * processes the converted record
     *
     * @param array $configuration
     * @param mixed $convertedRecord
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$convertedRecord, &$record)
    {
        return true;
    }
}

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class DummyInvalidPostProcessor
 * Does not implement PostProcessorInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidPostProcessor
{
}
/**
 * Class PostProcessorFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class PostProcessorFactoryTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\Factory\PostProcessorFactory
     */
    protected $subject;
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
                PostProcessorFactory::class, ['dummy']
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1447864207
     */
    public function getThrowsInvalidConfigurationExceptionIfClassIsNotSet()
    {
        $configurationWithoutClassName = ['bar'];

        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1447864223
     */
    public function getThrowsInvalidConfigurationExceptionIfClassDoesNotExist()
    {
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1447864243
     */
    public function getThrowsExceptionIfClassDoesNotImplementPostProcessorInterface()
    {
        $configurationWithExistingClass = [
            'class' => DummyInvalidPostProcessor::class
        ];
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    /**
     * @test
     */
    public function getReturnsPostProcessor()
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidPostProcessor::class;
        $validSingleConfiguration = ['foo' => 'bar'];
        $settings = [
            'class' => $validClass,
        ];
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $this->subject->injectObjectManager($mockObjectManager);
        $mockPreProcessor = $this->getMock($validClass);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($validClass)
            ->will($this->returnValue($mockPreProcessor));
        $this->assertEquals(
            $mockPreProcessor,
            $this->subject->get($settings, $identifier)
        );
    }
}
