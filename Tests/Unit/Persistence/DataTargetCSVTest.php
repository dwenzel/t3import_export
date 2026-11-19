<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataTargetCSV;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataTargetCSV::class)]
class DataTargetCSVTest extends TestCase
{
    use LegacyFileComponentTestTrait;

    private const string BOM = "\xEF\xBB\xBF";

    protected function setUp(): void
    {
        $this->setUpFilesAndDeprecations();
    }

    protected function tearDown(): void
    {
        $this->tearDownFilesAndDeprecations();
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function write(array $configuration, bool $setConfiguration = false): string
    {
        $subject = new DataTargetCSV($this->pathResolver());
        if ($setConfiguration) {
            $subject->setConfiguration($configuration);
            $configuration = null;
        }
        $subject->persist(['id' => 1, 'title' => 'First'], $configuration);
        $subject->persist(['id' => 2, 'title' => 'Second'], $configuration);
        $subject->persistAll(null, $configuration);

        return (string)file_get_contents($this->temporaryDirectory . '/out.csv');
    }

    #[Test]
    public function writesByteOrderMarkAndNoHeaderWithoutFields(): void
    {
        self::assertSame(self::BOM . "1,First\n2,Second\n", $this->write(['file' => 'out.csv']));
        self::assertDeprecationOf(DataTargetCSV::class, $this->deprecations);
    }

    #[Test]
    public function writesHeaderWithFields(): void
    {
        self::assertSame(
            self::BOM . "title\nFirst\nSecond\n",
            $this->write(['file' => 'out.csv', 'fields' => 'title'])
        );
    }

    #[Test]
    public function appliesTheDefaultsToTheConfigurationOfTheComponent(): void
    {
        self::assertSame(self::BOM . "1,First\n2,Second\n", $this->write(['file' => 'out.csv'], true));
    }

    #[Test]
    public function configuredOptionsOverrideTheDefaults(): void
    {
        self::assertSame(
            "id,title\n1,First\n2,Second\n",
            $this->write(['file' => 'out.csv', 'bom' => false, 'header' => true])
        );
    }
}
