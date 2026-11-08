<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\ClearCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
class ClearCacheTest extends TestCase
{
    protected ClearCache $subject;

    /**
     * @var CacheService|MockObject
     */
    protected $cacheService;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp(): void
    {
        $this->subject = new ClearCache();
        $this->mockCacheService();
    }

    protected function mockCacheService(): void
    {
        $this->cacheService = $this->getMockBuilder(CacheService::class)
            ->disableOriginalConstructor()
            ->setMethods(['clearPageCache'])
            ->getMock();
        $this->subject->injectCacheService($this->cacheService);
    }

    public function testProcessDoesNotClearCacheForEmptyResult(): void
    {
        $configuration = [];
        $records = ['foo'];
        $result = [];

        $this->cacheService->expects($this->never())
            ->method('clearPageCache');

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessClearsAllCachesIfGlobalOptionIsset(): void
    {
        $configuration = [
            'all' => '1'
        ];
        $records = ['foo'];
        $nonEmptyResult = ['bar'];

        $this->cacheService->expects($this->once())
            ->method('clearPageCache')
            ->with(null);

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    public function testProcessClearsSelectedPagesCachesIfGlobalOptionIsset(): void
    {
        $configuration = [
            'pages' => '1,5,7'
        ];
        $records = ['foo'];
        $nonEmptyResult = ['bar'];
        $expectedPagesToClear = GeneralUtility::intExplode(
            ',', $configuration['pages'], true
        );
        $this->cacheService->expects($this->once())
            ->method('clearPageCache')
            ->with($expectedPagesToClear);

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    public function testProcessClearsAllCachesIfResultClassMatchesConfiguration(): void
    {
        $configuration = [
            'classes' => [
                'stdClass' => [
                    'all' => '1'
                ]
            ]
        ];
        $records = ['foo'];
        $nonEmptyResult = [
            new stdClass()
        ];

        $this->cacheService->expects($this->once())
            ->method('clearPageCache')
            ->with(null);

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    public function testProcessClearsSelectedPageCachesIfResultClassMatchesConfiguration(): void
    {
        $configuration = [
            'classes' => [
                'stdClass' => [
                    'pages' => '1,5,7'
                ]
            ]
        ];
        $expectedPagesToClear = GeneralUtility::intExplode(
            ',', $configuration['classes']['stdClass']['pages'], true
        );

        $records = ['foo'];
        $nonEmptyResult = [
            new stdClass()
        ];

        $this->cacheService->expects($this->once())
            ->method('clearPageCache')
            ->with($expectedPagesToClear);

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    public function testIsConfigurationValidAlwaysReturnsTrue(): void
    {
        $configuration = [];
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testProcessSkipsIfResultClassDoesNotMatch(): void
    {
        $configuration = [
            'classes' => [
                'NonMatchingClassName' => [
                    'pages' => '1,5,7'
                ]
            ]
        ];

        $records = ['foo'];
        $nonEmptyResult = [
            new stdClass()
        ];

        $this->cacheService->expects($this->never())
            ->method('clearPageCache');

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }
}
