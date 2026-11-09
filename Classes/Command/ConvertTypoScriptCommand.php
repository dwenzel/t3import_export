<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

use CPSIT\T3importExport\Configuration\TypoScriptConverter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Converts TypoScript settings of the module to YAML
 */
#[AsCommand(
    name: 't3import-export:convert-typoscript',
    description: 'Converts tasks and sets from TypoScript (module.tx_t3importexport.settings) to YAML',
)]
class ConvertTypoScriptCommand extends Command
{
    public const ARGUMENT_FILES = 'files';
    public const OPTION_OUTPUT = 'output';
    public const OPTION_CONSTANTS = 'constants';

    public function __construct(protected readonly TypoScriptConverter $converter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::ARGUMENT_FILES,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'TypoScript files (EXT: paths or paths relative to the project), read in the given order. Imports with EXT: paths are resolved.'
            )
            ->addOption(
                self::OPTION_OUTPUT,
                'o',
                InputOption::VALUE_REQUIRED,
                'Write the YAML to this file instead of the standard output'
            )
            ->addOption(
                self::OPTION_CONSTANTS,
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Constants files: TypoScript, or YAML (*.yaml, *.yml, e.g. site settings; a top-level "settings" key is used if present); their values replace {$...} in the settings. TypoScript constants take precedence.'
            )
            ->setHelp(
                'Content objects (e.g. TEXT, COA) and constants ({$...}) without a value are kept as they are and listed;'
                . ' they have to be migrated by hand, see Documentation/DEPRECATIONS.md.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $messages = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $constantFiles = (array)$input->getOption(self::OPTION_CONSTANTS);
        $yamlFiles = array_filter($constantFiles, static fn($file): bool => (bool)preg_match('/\.ya?ml$/i', (string)$file));
        $typoScript = $this->readFiles((array)$input->getArgument(self::ARGUMENT_FILES), $messages);
        $constants = $this->readFiles(array_diff($constantFiles, $yamlFiles), $messages);
        if ($typoScript === null || $constants === null) {
            return Command::FAILURE;
        }
        $additionalConstants = [];
        foreach ($yamlFiles as $file) {
            $path = $this->resolvePath((string)$file);
            if ($path === null) {
                $messages->writeln(sprintf('<error>File not found: %s</error>', $file));
                return Command::FAILURE;
            }
            $values = Yaml::parseFile($path);
            // site config.yaml or settings.yaml with a "settings" key
            if (is_array($values) && is_array($values['settings'] ?? null)) {
                $values = $values['settings'];
            }
            if (is_array($values)) {
                $additionalConstants = array_replace_recursive($additionalConstants, $values);
            }
        }

        $result = $this->converter->convert($typoScript, $constants, $additionalConstants);
        if ($result['configuration'] === []) {
            $messages->writeln('<error>No tasks or sets found in module.tx_t3importexport.settings</error>');
            return Command::FAILURE;
        }

        $yaml = Yaml::dump($result['configuration'], 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $outputFile = $input->getOption(self::OPTION_OUTPUT);
        if (is_string($outputFile) && $outputFile !== '') {
            if (file_put_contents($outputFile, $yaml) === false) {
                $messages->writeln(sprintf('<error>Could not write %s</error>', $outputFile));
                return Command::FAILURE;
            }
            $messages->writeln(sprintf('Written to %s', $outputFile));
        } else {
            $output->write($yaml);
        }

        foreach ($result['contentObjects'] as $path => $type) {
            $messages->writeln(sprintf('<comment>Content object %s at %s: migrate by hand (deprecated)</comment>', $type, $path));
        }
        foreach ($result['constants'] as $path => $value) {
            $messages->writeln(sprintf('<comment>Constant at %s not substituted: %s</comment>', $path, $value));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $files
     * @return string|null Contents of all files, null if a file is missing
     */
    protected function readFiles(array $files, OutputInterface $messages): ?string
    {
        $contents = '';
        foreach ($files as $file) {
            $path = $this->resolvePath((string)$file);
            if ($path === null) {
                $messages->writeln(sprintf('<error>File not found: %s</error>', $file));
                return null;
            }
            $contents .= file_get_contents($path) . "\n";
        }

        return $contents;
    }

    protected function resolvePath(string $file): ?string
    {
        if (!str_starts_with($file, 'EXT:') && is_file($file)) {
            return $file;
        }
        $path = GeneralUtility::getFileAbsFileName($file);

        return $path !== '' && is_file($path) ? $path : null;
    }
}
