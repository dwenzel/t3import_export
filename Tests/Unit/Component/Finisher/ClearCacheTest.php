<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\ClearCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\CacheManager;

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

    protected CacheManager&MockObject $cacheManager;

    protected function setUp(): void
    {
        $this->subject = new ClearCache();
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->subject->injectCacheManager($this->cacheManager);
    }

    protected function expectNoCacheFlush(): void
    {
        $this->cacheManager->expects($this->never())->method('flushCachesInGroup');
        $this->cacheManager->expects($this->never())->method('flushCachesInGroupByTags');
    }

    protected function expectAllPageCachesFlushed(): void
    {
        $this->cacheManager->expects($this->once())
            ->method('flushCachesInGroup')
            ->with('pages');
        $this->cacheManager->expects($this->never())->method('flushCachesInGroupByTags');
    }

    protected function expectPageCachesFlushed(array $tags): void
    {
        $this->cacheManager->expects($this->never())->method('flushCachesInGroup');
        $this->cacheManager->expects($this->once())
            ->method('flushCachesInGroupByTags')
            ->with('pages', $tags);
    }

    #[Test]
    public function testProcessDoesNotClearCacheForEmptyResult(): void
    {
        $configuration = [];
        $records = ['foo'];
        $result = [];

        $this->expectNoCacheFlush();

        $this->subject->process($configuration, $records, $result);
    }

    #[Test]
    public function testProcessClearsAllCachesIfGlobalOptionIsset(): void
    {
        $configuration = [
            'all' => '1',
        ];
        $records = ['foo'];
        $nonEmptyResult = ['bar'];

        $this->expectAllPageCachesFlushed();

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    #[Test]
    public function testProcessClearsSelectedPagesCachesIfGlobalOptionIsset(): void
    {
        $configuration = [
            'pages' => '1,5,7',
        ];
        $records = ['foo'];
        $nonEmptyResult = ['bar'];
        $this->expectPageCachesFlushed(['pageId_1', 'pageId_5', 'pageId_7']);

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    #[Test]
    public function testProcessClearsAllCachesIfResultClassMatchesConfiguration(): void
    {
        $configuration = [
            'classes' => [
                'stdClass' => [
                    'all' => '1',
                ],
            ],
        ];
        $records = ['foo'];
        $nonEmptyResult = [
            new \stdClass(),
        ];

        $this->expectAllPageCachesFlushed();

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    #[Test]
    public function testProcessClearsSelectedPageCachesIfResultClassMatchesConfiguration(): void
    {
        $configuration = [
            'classes' => [
                'stdClass' => [
                    'pages' => '1,5,7',
                ],
            ],
        ];
        $records = ['foo'];
        $nonEmptyResult = [
            new \stdClass(),
        ];

        $this->expectPageCachesFlushed(['pageId_1', 'pageId_5', 'pageId_7']);

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }

    #[Test]
    public function testIsConfigurationValidAlwaysReturnsTrue(): void
    {
        $configuration = [];
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[Test]
    public function testProcessSkipsIfResultClassDoesNotMatch(): void
    {
        $configuration = [
            'classes' => [
                'NonMatchingClassName' => [
                    'pages' => '1,5,7',
                ],
            ],
        ];

        $records = ['foo'];
        $nonEmptyResult = [
            new \stdClass(),
        ];

        $this->expectNoCacheFlush();

        $this->subject->process($configuration, $records, $nonEmptyResult);
    }
}
