<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Configuration;

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;

/**
 * Converts the TypoScript settings of the module
 * (`module.tx_t3importexport.settings`) to the YAML configuration schema.
 *
 * Imports with `EXT:` paths are resolved. Content objects (values with
 * sub-properties, e.g. `TEXT`) are kept as they are and reported, since
 * they have to be migrated by hand (see Documentation/DEPRECATIONS.md).
 * Constants (`{$...}`) are substituted with the values of the given constants
 * TypoScript and additional values (e.g. site settings); constants without a
 * value are kept and reported.
 */
class TypoScriptConverter
{
    public const TYPES = ['import', 'export'];
    public const KINDS = ['tasks', 'sets'];

    public function __construct(
        protected readonly TypoScriptStringFactory $typoScriptStringFactory,
        protected readonly TypoScriptService $typoScriptService
    ) {}

    /**
     * @param array<mixed> $additionalConstants nested values, e.g. site settings; TypoScript constants take precedence
     * @return array{configuration: array<string, array<string, array<mixed>>>, contentObjects: array<string, string>, constants: array<string, string>}
     */
    public function convert(string $typoScript, string $constantsTypoScript = '', array $additionalConstants = []): array
    {
        $rootNode = $this->typoScriptStringFactory->parseFromStringWithIncludes('t3import_export-converter', $typoScript);
        $typoScriptArray = $rootNode->toArray();
        $settings = $typoScriptArray['module.']['tx_t3importexport.']['settings.'] ?? [];
        $settings = is_array($settings) ? $this->typoScriptService->convertTypoScriptArrayToPlainArray($settings) : [];

        $configuration = [];
        foreach (self::TYPES as $type) {
            foreach (self::KINDS as $kind) {
                if (!empty($settings[$type][$kind]) && is_array($settings[$type][$kind])) {
                    $configuration[$type][$kind] = $settings[$type][$kind];
                }
            }
        }

        $constantValues = $this->flattenArray($additionalConstants);
        if ($constantsTypoScript !== '') {
            $constantValues = array_replace(
                $constantValues,
                $this->flattenTypoScriptArray(
                    $this->typoScriptStringFactory
                        ->parseFromStringWithIncludes('t3import_export-converter-constants', $constantsTypoScript)
                        ->toArray()
                )
            );
        }
        if ($constantValues !== []) {
            $configuration = $this->substituteConstants($configuration, $constantValues);
        }

        $contentObjects = [];
        $constants = [];
        $this->collectFindings($configuration, '', $contentObjects, $constants);

        return [
            'configuration' => $configuration,
            'contentObjects' => $contentObjects,
            'constants' => $constants,
        ];
    }

    /**
     * @param array<mixed> $typoScriptArray
     * @return array<string, string> constant name => value
     */
    protected function flattenTypoScriptArray(array $typoScriptArray, string $prefix = ''): array
    {
        $flat = [];
        foreach ($typoScriptArray as $key => $value) {
            if (is_array($value)) {
                $flat += $this->flattenTypoScriptArray($value, $prefix . $key);
                continue;
            }
            $flat[$prefix . $key] = (string)$value;
        }

        return $flat;
    }

    /**
     * @param array<mixed> $values nested values, keys are joined with dots
     * @return array<string, string>
     */
    protected function flattenArray(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $flat += $this->flattenArray($value, $prefix . $key . '.');
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $flat[$prefix . $key] = (string)$value;
        }

        return $flat;
    }

    /**
     * @param array<mixed> $values
     * @param array<string, string> $constantValues
     * @return array<mixed>
     */
    protected function substituteConstants(array $values, array $constantValues): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->substituteConstants($value, $constantValues);
            } elseif (is_string($value) && str_contains($value, '{$')) {
                $values[$key] = (string)preg_replace_callback(
                    '/\{\$([^}]+)\}/',
                    static fn(array $matches): string => $constantValues[trim($matches[1])] ?? $matches[0],
                    $value
                );
            }
        }

        return $values;
    }

    /**
     * @param array<mixed> $values
     * @param array<string, string> $contentObjects path => object type
     * @param array<string, string> $constants path => value
     */
    protected function collectFindings(array $values, string $path, array &$contentObjects, array &$constants): void
    {
        foreach ($values as $key => $value) {
            $currentPath = $path === '' ? (string)$key : $path . '.' . $key;
            if (is_array($value)) {
                if (isset($value['_typoScriptNodeValue'])) {
                    $contentObjects[$currentPath] = (string)$value['_typoScriptNodeValue'];
                }
                $this->collectFindings($value, $currentPath, $contentObjects, $constants);
                continue;
            }
            if (is_string($value) && str_contains($value, '{$')) {
                $constants[$currentPath] = $value;
            }
        }
    }
}
