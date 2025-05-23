<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Configuration;

use CPSIT\ImportExportCore\Configuration\ConfigurationManager;
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use CPSIT\T3importExport\Configuration\YamlConfigurationProvider;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class YamlConfigurationProviderTest extends TestCase
{
    protected YamlConfigurationProvider $subject;
    protected ConfigurationManager $configurationManager;
    protected YamlConfigurationLoader $yamlLoader;
    protected vfsStreamDirectory $root;
    
    protected function setUp(): void
    {
        $this->configurationManager = $this->createMock(ConfigurationManager::class);
        $this->yamlLoader = $this->createMock(YamlConfigurationLoader::class);
        
        $this->subject = new YamlConfigurationProvider(
            $this->configurationManager,
            $this->yamlLoader
        );
        
        $this->root = vfsStream::setup('home');
    }
    
    public function testLoadFromDirectoryProcessesYamlFiles(): void
    {
        vfsStream::newFile('config1.yaml')->at($this->root)->withContent('');
        vfsStream::newFile('config2.yaml')->at($this->root)->withContent('');
        vfsStream::newFile('other.txt')->at($this->root)->withContent('');
        
        $matcher = $this->exactly(2);
        $this->configurationManager->expects($matcher)
            ->method('addConfiguration')
            ->willReturnCallback(function ($loader, $path) use ($matcher) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    $this->assertSame($this->yamlLoader, $loader);
                    $this->assertEquals($this->root->url() . '/config1.yaml', $path);
                } elseif ($callCount === 2) {
                    $this->assertSame($this->yamlLoader, $loader);
                    $this->assertEquals($this->root->url() . '/config2.yaml', $path);
                }
            });
        
        $this->configurationManager->expects($this->once())
            ->method('getFullConfiguration')
            ->willReturn(['some' => 'config']);
        
        $result = $this->subject->loadFromDirectory($this->root->url());
        
        $this->assertEquals(['some' => 'config'], $result);
    }
    
    public function testLoadFromDirectoryHandlesEmptyDirectory(): void
    {
        $this->configurationManager->expects($this->never())
            ->method('addConfiguration');
        
        $this->configurationManager->expects($this->once())
            ->method('getFullConfiguration')
            ->willReturn([]);
        
        $result = $this->subject->loadFromDirectory($this->root->url());
        
        $this->assertEquals([], $result);
    }
    
    public function testLoadFromDirectoryWithCustomExtension(): void
    {
        vfsStream::newFile('config1.yml')->at($this->root)->withContent('');
        vfsStream::newFile('config2.yml')->at($this->root)->withContent('');
        vfsStream::newFile('config3.yaml')->at($this->root)->withContent('');
        
        $matcher = $this->exactly(2);
        $this->configurationManager->expects($matcher)
            ->method('addConfiguration')
            ->willReturnCallback(function ($loader, $path) use ($matcher) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    $this->assertSame($this->yamlLoader, $loader);
                    $this->assertEquals($this->root->url() . '/config1.yml', $path);
                } elseif ($callCount === 2) {
                    $this->assertSame($this->yamlLoader, $loader);
                    $this->assertEquals($this->root->url() . '/config2.yml', $path);
                }
            });
        
        $this->configurationManager->expects($this->once())
            ->method('getFullConfiguration')
            ->willReturn(['some' => 'config']);
        
        $result = $this->subject->loadFromDirectory($this->root->url(), 'yml');
        
        $this->assertEquals(['some' => 'config'], $result);
    }
}