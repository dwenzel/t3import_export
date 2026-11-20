<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Factory;

use CPSIT\ImportExportCore\Persistence\NullDataSource;
use CPSIT\T3importExport\Factory\Typo3Instantiator;
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
        $instance = new NullDataSource();
        GeneralUtility::addInstance(NullDataSource::class, $instance);

        $created = (new Typo3Instantiator())->instantiate(NullDataSource::class);

        self::assertInstanceOf(NullDataSource::class, $created);
        self::assertNotSame($instance, $created);
    }
}
