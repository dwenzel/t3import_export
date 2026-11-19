<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceCSV;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSourceCSV::class)]
class DataSourceCSVTest extends TestCase
{
    use LegacyFileComponentTestTrait;

    protected function setUp(): void
    {
        $this->setUpFilesAndDeprecations();
    }

    protected function tearDown(): void
    {
        $this->tearDownFilesAndDeprecations();
    }

    #[Test]
    public function getRecordsReadsRowsAndTriggersDeprecation(): void
    {
        $this->writeFile('courses.csv', "id,title\n1,First\n2,Second\n");
        $subject = new DataSourceCSV($this->resourceLoader());

        $records = iterator_to_array($subject->getRecords(['file' => 'courses.csv']), false);

        self::assertSame(
            [['id' => '1', 'title' => 'First'], ['id' => '2', 'title' => 'Second']],
            $records
        );
        self::assertDeprecationOf(DataSourceCSV::class, $this->deprecations);
    }

    #[Test]
    public function fieldsNameTheColumnsOfAFileWithoutHeader(): void
    {
        $this->writeFile('courses.csv', "1;First\n2;Second\n");
        $subject = new DataSourceCSV($this->resourceLoader());

        $records = iterator_to_array(
            $subject->getRecords(['file' => 'courses.csv', 'fields' => 'id, title', 'delimiter' => ';']),
            false
        );

        self::assertSame(
            [['id' => '1', 'title' => 'First'], ['id' => '2', 'title' => 'Second']],
            $records
        );
    }
}
