<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\T3importExport\Configuration\YamlConfigurationProvider;
use CPSIT\T3importExport\Extension;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionTest extends TestCase
{
    protected Extension $subject;
    
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(Extension::class)
            ->onlyMethods(['loadYamlConfigurations'])
            ->getMock();
    }
    
    public function testBootSetsDefaultYamlConfigurationDirectoriesIfNotSet(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']);
        
        $this->subject->boot();
        
        $this->assertIsArray($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']);
        $this->assertContains(
            'EXT:t3import_export/Configuration/ImportExport', 
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
        );
    }
    
    public function testBootDoesNotOverrideExistingYamlConfigurationDirectories(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'] = [
            'EXT:custom_extension/Configuration/ImportExport'
        ];
        
        $this->subject->boot();
        
        $this->assertIsArray($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']);
        $this->assertContains(
            'EXT:custom_extension/Configuration/ImportExport', 
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
        );
        $this->assertNotContains(
            'EXT:t3import_export/Configuration/ImportExport', 
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
        );
    }
    
    public function testBootCallsLoadYamlConfigurationsIfBootstrapIsComplete(): void
    {
        // Mock the bootstrap check
        $bootstrapMock = $this->createMock(Bootstrap::class);
        $bootstrapReflection = new \ReflectionClass(Bootstrap::class);
        $bootstrapMethod = $bootstrapReflection->getMethod('checkIfEssentialConfigurationExists');
        
        if ($bootstrapMethod->isStatic()) {
            // For static methods
            $this->subject = $this->getMockBuilder(Extension::class)
                ->onlyMethods(['loadYamlConfigurations'])
                ->getMock();
            
            // Expect loadYamlConfigurations to be called
            $this->subject->expects($this->once())
                ->method('loadYamlConfigurations');
        }
        
        $this->subject->boot();
    }
}