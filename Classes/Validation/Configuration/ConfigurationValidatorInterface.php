<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Validation\Configuration;

/**
 * Interface ConfigurationValidatorInterface
 */
interface ConfigurationValidatorInterface
{
    public const KEY_CLASS = 'class';
    public const KEY_CONFIG = 'config';
    public const KEY_PARENT_FIELD = 'parentField';
    public const KEY_MAPPING = 'mapping';
    public const KEY_TARGET_CLASS = 'targetClass';
    public const KEY_LANGUAGE = 'language';
    public const KEY_TABLE = 'table';
    public const KEY_MATCH_FIELD = 'matchField';
    public const KEY_IDENTITY_FIELD = 'identityField';
    public const KEY_PREFIX = 'prefix';
    public const KEY_PARENT = 'parent';

    /**
     * @return bool Returns true for valid configuration
     */
    public function isValid(array $config): bool;
}
