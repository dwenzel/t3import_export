<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceXML;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSourceXML::class)]
class DataSourceXMLTest extends TestCase
{
    use LegacyFileComponentTestTrait;

    private const string XML = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <courses>
          <course id="1"><title>First</title></course>
          <course id="2"><title>Second</title></course>
        </courses>
        XML;

    protected function setUp(): void
    {
        $this->setUpFilesAndDeprecations();
        $this->writeFile('courses.xml', self::XML);
    }

    protected function tearDown(): void
    {
        $this->tearDownFilesAndDeprecations();
    }

    public static function expressionDataProvider(): iterable
    {
        yield 'absolute' => ['//course'];
        yield 'relative to the root element' => ['course'];
        yield 'relative with leading space' => [' course'];
    }

    #[Test]
    #[DataProvider('expressionDataProvider')]
    public function getRecordsSelectsElementsLikeBefore(string $expression): void
    {
        $subject = new DataSourceXML($this->resourceLoader());
        $configuration = ['file' => 'courses.xml', 'expression' => $expression];

        self::assertTrue($subject->isConfigurationValid($configuration));
        $records = iterator_to_array($subject->getRecords($configuration), false);

        self::assertSame(
            [
                ['@attributes' => ['id' => '1'], 'title' => 'First'],
                ['@attributes' => ['id' => '2'], 'title' => 'Second'],
            ],
            $records
        );
        self::assertDeprecationOf(DataSourceXML::class, $this->deprecations);
    }

    #[Test]
    public function getRecordsKeepsExpressionsStartingWithParenthesis(): void
    {
        $subject = new DataSourceXML($this->resourceLoader());

        $records = iterator_to_array(
            $subject->getRecords(['file' => 'courses.xml', 'expression' => '(//course)[2]']),
            false
        );

        self::assertSame([['@attributes' => ['id' => '2'], 'title' => 'Second']], $records);
    }

    #[Test]
    public function isConfigurationValidRequiresAnExpressionOrAnElement(): void
    {
        $subject = new DataSourceXML($this->resourceLoader());

        self::assertFalse($subject->isConfigurationValid(['file' => 'courses.xml']));
        self::assertFalse($subject->isConfigurationValid(['file' => 'courses.xml', 'expression' => ' ']));
        self::assertTrue($subject->isConfigurationValid(['file' => 'courses.xml', 'element' => 'course']));
    }
}
