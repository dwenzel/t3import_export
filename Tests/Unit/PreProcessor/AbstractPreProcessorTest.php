<?php
namespace CPSIT\T3importExport\Tests\PreProcessor;

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class AbstractPreProcessorTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor
 */
class AbstractPreProcessorTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMockForAbstractClass(
            'CPSIT\\T3importExport\\Component\\PreProcessor\\AbstractPreProcessor',
            [], '', false);
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsAlwaysTrue()
    {
        $mockConfiguration = ['foo'];
        $this->assertTrue(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @cover ::injectTypoScriptService
     */
    public function injectTypoScriptServiceSetsTypoScriptService()
    {
        $mockTypoScriptService = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService'
        );

        $this->subject->injectTypoScriptService($mockTypoScriptService);
        $this->assertSame(
            $mockTypoScriptService,
            $this->subject->_get('typoScriptService')
        );
    }

    /**
     * @test
     * @covers ::isDisabled
     */
    public function isDisabledReturnsInitiallyFalse()
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->isDisabled($configuration)
        );
    }

    /**
     * @test
     * @covers ::isDisabled
     */
    public function isDisabledReturnsTrueIfDisabledIsSet()
    {
        $configuration = [
            'disable' => '1'
        ];
        $this->assertTrue(
            $this->subject->isDisabled($configuration)
        );
    }

    /**
     * @test
     * @covers ::isDisabled
     */
    public function isDisabledRendersContent()
    {
        $subject = $this->getAccessibleMock(
            'CPSIT\\T3importExport\\Component\\PreProcessor\\AbstractPreProcessor',
            ['renderContent', 'process'], [], '', false);
        $configuration = [
            'disable' => [
                'value' => '1',
                'if' => [
                    'isTrue' => '1'
                ],
                '_typoScriptNodeValue' => 'TEXT',
            ]
        ];

        $subject->expects($this->once())
            ->method('renderContent')
            ->with([], $configuration['disable']);

        $subject->isDisabled($configuration);
    }
}
