<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Configuration;

use CPSIT\T3importExport\Command\ConvertTypoScriptCommand;
use CPSIT\T3importExport\Configuration\TypoScriptConverter;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Conversion of TypoScript settings to YAML (#82)
 */
class TypoScriptConverterTest extends FunctionalTestCase
{
    protected const FIXTURE = 'EXT:t3import_export/Tests/Functional/Fixtures/TypoScript/setup.typoscript';

    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function convertReturnsTasksAndSetsIncludingImports(): void
    {
        $subject = $this->get(TypoScriptConverter::class);
        $typoScript = (string)file_get_contents(__DIR__ . '/../Fixtures/TypoScript/setup.typoscript');

        $result = $subject->convert($typoScript);

        self::assertSame(
            [
                'import' => [
                    'tasks' => [
                        'event' => [
                            'label' => 'Events',
                            'source' => [
                                'class' => 'CPSIT\T3importExport\Persistence\DataSourceCSV',
                                'config' => ['file' => 'EXT:example/Resources/Private/events.csv'],
                            ],
                            'preProcessors' => [
                                10 => [
                                    'class' => 'CPSIT\T3importExport\Component\PreProcessor\RenderContent',
                                    'config' => [
                                        'fields' => [
                                            'teaser' => [
                                                'field' => 'teaser // abstract',
                                                '_typoScriptNodeValue' => 'TEXT',
                                            ],
                                        ],
                                    ],
                                ],
                                20 => [
                                    'class' => 'CPSIT\T3importExport\Component\PreProcessor\SetFieldValue',
                                    'config' => ['targetField' => 'pid', 'value' => '12'],
                                ],
                            ],
                        ],
                    ],
                    'sets' => [
                        'all' => ['label' => 'All', 'tasks' => 'event'],
                    ],
                ],
                'export' => [
                    'tasks' => [
                        'dump' => [
                            'source' => [
                                'class' => 'CPSIT\T3importExport\Persistence\DataSourceDB',
                                'config' => ['table' => '{$plugin.example.table}'],
                            ],
                        ],
                    ],
                ],
            ],
            $result['configuration']
        );
        self::assertSame(
            ['import.tasks.event.preProcessors.10.config.fields.teaser' => 'TEXT'],
            $result['contentObjects']
        );
        self::assertSame(
            ['export.tasks.dump.source.config.table' => '{$plugin.example.table}'],
            $result['constants']
        );
    }

    #[Test]
    public function commandWritesYamlThatCanBeParsed(): void
    {
        $commandTester = new CommandTester($this->get(ConvertTypoScriptCommand::class));

        $exitCode = $commandTester->execute(['files' => [self::FIXTURE]], ['capture_stderr_separately' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'Content object TEXT at import.tasks.event.preProcessors.10.config.fields.teaser',
            $commandTester->getErrorOutput()
        );
        self::assertStringContainsString('Constant at export.tasks.dump.source.config.table', $commandTester->getErrorOutput());
        $yaml = Yaml::parse($commandTester->getDisplay());
        self::assertSame('Events', $yaml['import']['tasks']['event']['label']);
        self::assertSame('event', $yaml['import']['sets']['all']['tasks']);
    }

    #[Test]
    public function commandFailsForMissingFile(): void
    {
        $commandTester = new CommandTester($this->get(ConvertTypoScriptCommand::class));

        self::assertSame(Command::FAILURE, $commandTester->execute(['files' => ['EXT:t3import_export/missing.typoscript']]));
    }

    #[Test]
    public function convertSubstitutesConstants(): void
    {
        $subject = $this->get(TypoScriptConverter::class);
        $typoScript = (string)file_get_contents(__DIR__ . '/../Fixtures/TypoScript/setup.typoscript');
        $constants = (string)file_get_contents(__DIR__ . '/../Fixtures/TypoScript/constants.typoscript');

        $result = $subject->convert($typoScript, $constants);

        self::assertSame(
            'tx_example_table',
            $result['configuration']['export']['tasks']['dump']['source']['config']['table']
        );
        self::assertSame([], $result['constants']);
    }

    #[Test]
    public function convertSubstitutesAdditionalConstantsWithTypoScriptConstantsTakingPrecedence(): void
    {
        $subject = $this->get(TypoScriptConverter::class);
        $typoScript = (string)file_get_contents(__DIR__ . '/../Fixtures/TypoScript/setup.typoscript');
        $typoScript .= PHP_EOL . 'module.tx_t3importexport.settings.export.tasks.dump.target.config.pid = {$plugin.example.pids.1}' . PHP_EOL;
        $constants = (string)file_get_contents(__DIR__ . '/../Fixtures/TypoScript/constants.typoscript');
        $settings = Yaml::parseFile(__DIR__ . '/../Fixtures/TypoScript/settings.yaml');

        $result = $subject->convert($typoScript, $constants, $settings);

        $task = $result['configuration']['export']['tasks']['dump'];
        self::assertSame('tx_example_table', $task['source']['config']['table']);
        self::assertSame('13', $task['target']['config']['pid']);
        self::assertSame([], $result['constants']);
    }
}
