<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Factory;

use CPSIT\T3importExport\Factory\Typo3Instantiator;
use CPSIT\T3importExport\Persistence\DataSourceDummy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[CoversClass(Typo3Instantiator::class)]
class Typo3InstantiatorTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function instantiateReturnsCloneOfInstanceFromGeneralUtility(): void
    {
        $instance = new DataSourceDummy();
        GeneralUtility::addInstance(DataSourceDummy::class, $instance);

        $created = (new Typo3Instantiator())->instantiate(DataSourceDummy::class);

        self::assertInstanceOf(DataSourceDummy::class, $created);
        self::assertNotSame($instance, $created);
    }
}
