<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Unit\Configuration;

/***************************************************************
 *  Copyright notice
 *  (c) 2025 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use CPSIT\ImportExportCore\Exception\FileNotFoundException;
use CPSIT\ImportExportCore\Exception\ParseException;
use CPSIT\ImportExportCore\Service\YamlConfigurationParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class YamlConfigurationLoaderTest
 * Integration tests for CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader
 */
class YamlConfigurationLoaderTest extends TestCase
{
    protected YamlConfigurationLoader $yamlConfigurationLoader;
    protected string $tempYamlFile;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $parser = new YamlConfigurationParser();
        $this->yamlConfigurationLoader = new YamlConfigurationLoader($parser);
        $this->tempYamlFile = tempnam(sys_get_temp_dir(), 'test_config_') . '.yaml';
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tempYamlFile)) {
            unlink($this->tempYamlFile);
        }
        parent::tearDown();
    }

    #[Test]
    public function loadThrowsFileNotFoundExceptionForNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Configuration file not found: /non/existent/file.yaml');
        
        $this->yamlConfigurationLoader->load('/non/existent/file.yaml');
    }

    #[Test]
    public function loadThrowsParseExceptionForInvalidYaml(): void
    {
        file_put_contents($this->tempYamlFile, "invalid:\n  yaml:\n    content\n  missing_colon");
        
        $this->expectException(ParseException::class);
        
        $this->yamlConfigurationLoader->load($this->tempYamlFile);
    }

    #[Test]
    public function loadHandlesEmptyYamlFile(): void
    {
        file_put_contents($this->tempYamlFile, '# Empty YAML file\n');
        
        $this->expectException(\TypeError::class);
        
        $this->yamlConfigurationLoader->load($this->tempYamlFile);
    }

    #[Test]
    public function loadConvertsYamlConfigurationToTypoScriptFormat(): void
    {
        $yamlContent = <<<YAML
import:
  tasks:
    importPages:
      source:
        class: 'TestSource'
        config:
          table: 'pages'
      target:
        class: 'TestTarget'
        config:
          table: 'pages_copy'
  sets:
    testSet:
      tasks: 'importPages'
      description: 'Test import set'
export:
  tasks:
    exportPages:
      source:
        class: 'TestSource'
      target:
        class: 'TestTarget'
  sets:
    exportSet:
      tasks: 'exportPages'
YAML;
        
        file_put_contents($this->tempYamlFile, $yamlContent);
        
        $result = $this->yamlConfigurationLoader->load($this->tempYamlFile);
        
        $expected = [
            'module' => [
                'tx_t3importexport' => [
                    'settings' => [
                        'import' => [
                            'tasks' => [
                                'importPages' => [
                                    'source' => [
                                        'class' => 'TestSource',
                                        'config' => ['table' => 'pages']
                                    ],
                                    'target' => [
                                        'class' => 'TestTarget',
                                        'config' => ['table' => 'pages_copy']
                                    ]
                                ]
                            ],
                            'sets' => [
                                'testSet' => [
                                    'tasks' => 'importPages',
                                    'description' => 'Test import set'
                                ]
                            ]
                        ],
                        'export' => [
                            'tasks' => [
                                'exportPages' => [
                                    'source' => [
                                        'class' => 'TestSource'
                                    ],
                                    'target' => [
                                        'class' => 'TestTarget'
                                    ]
                                ]
                            ],
                            'sets' => [
                                'exportSet' => [
                                    'tasks' => 'exportPages'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function loadHandlesPartialConfigurationWithOnlyImport(): void
    {
        $yamlContent = <<<YAML
import:
  tasks:
    importPages:
      source:
        class: 'TestSource'
      target:
        class: 'TestTarget'
YAML;
        
        file_put_contents($this->tempYamlFile, $yamlContent);
        
        $result = $this->yamlConfigurationLoader->load($this->tempYamlFile);
        
        $this->assertArrayHasKey('module', $result);
        $this->assertArrayHasKey('import', $result['module']['tx_t3importexport']['settings']);
        $this->assertArrayNotHasKey('export', $result['module']['tx_t3importexport']['settings']);
        $this->assertArrayHasKey('importPages', $result['module']['tx_t3importexport']['settings']['import']['tasks']);
    }

    #[Test]
    public function loadHandlesPartialConfigurationWithOnlyExport(): void
    {
        $yamlContent = <<<YAML
export:
  tasks:
    exportPages:
      source:
        class: 'TestSource'
      target:
        class: 'TestTarget'
YAML;
        
        file_put_contents($this->tempYamlFile, $yamlContent);
        
        $result = $this->yamlConfigurationLoader->load($this->tempYamlFile);
        
        $this->assertArrayHasKey('module', $result);
        $this->assertArrayNotHasKey('import', $result['module']['tx_t3importexport']['settings']);
        $this->assertArrayHasKey('export', $result['module']['tx_t3importexport']['settings']);
        $this->assertArrayHasKey('exportPages', $result['module']['tx_t3importexport']['settings']['export']['tasks']);
    }

    #[Test]
    public function loadReturnsEmptyArrayForEmptyConfiguration(): void
    {
        file_put_contents($this->tempYamlFile, '{}');
        
        $result = $this->yamlConfigurationLoader->load($this->tempYamlFile);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function loadHandlesComplexConfigurationWithComponents(): void
    {
        $yamlContent = <<<YAML
import:
  tasks:
    complexTask:
      source:
        class: 'CPSIT\T3importExport\Persistence\DataSourceCSV'
        config:
          file: '/path/to/data.csv'
          delimiter: ';'
      target:
        class: 'CPSIT\T3importExport\Persistence\DataTargetDB'
        config:
          table: 'target_table'
      initializers:
        - class: 'CPSIT\T3importExport\Component\Initializer\TruncateTables'
          config:
            tables: 'target_table'
      preProcessors:
        - class: 'CPSIT\T3importExport\Component\PreProcessor\MapFields'
          config:
            map:
              old_field: 'new_field'
      postProcessors:
        - class: 'CPSIT\T3importExport\Component\PostProcessor\SetL10nParent'
          config:
            parentField: 'l10n_parent'
      finishers:
        - class: 'CPSIT\T3importExport\Component\Finisher\ClearCache'
          config:
            cacheGroups: 'pages,all'
YAML;
        
        file_put_contents($this->tempYamlFile, $yamlContent);
        
        $result = $this->yamlConfigurationLoader->load($this->tempYamlFile);
        
        $task = $result['module']['tx_t3importexport']['settings']['import']['tasks']['complexTask'];
        
        $this->assertEquals('CPSIT\T3importExport\Persistence\DataSourceCSV', $task['source']['class']);
        $this->assertEquals('/path/to/data.csv', $task['source']['config']['file']);
        $this->assertEquals(';', $task['source']['config']['delimiter']);
        
        $this->assertEquals('CPSIT\T3importExport\Persistence\DataTargetDB', $task['target']['class']);
        $this->assertEquals('target_table', $task['target']['config']['table']);
        
        $this->assertArrayHasKey('initializers', $task);
        $this->assertCount(1, $task['initializers']);
        $this->assertEquals('CPSIT\T3importExport\Component\Initializer\TruncateTables', $task['initializers'][0]['class']);
        
        $this->assertArrayHasKey('preProcessors', $task);
        $this->assertCount(1, $task['preProcessors']);
        $this->assertEquals('CPSIT\T3importExport\Component\PreProcessor\MapFields', $task['preProcessors'][0]['class']);
        
        $this->assertArrayHasKey('postProcessors', $task);
        $this->assertCount(1, $task['postProcessors']);
        $this->assertEquals('CPSIT\T3importExport\Component\PostProcessor\SetL10nParent', $task['postProcessors'][0]['class']);
        
        $this->assertArrayHasKey('finishers', $task);
        $this->assertCount(1, $task['finishers']);
        $this->assertEquals('CPSIT\T3importExport\Component\Finisher\ClearCache', $task['finishers'][0]['class']);
    }
}