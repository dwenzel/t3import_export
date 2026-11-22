<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Utility;

/**
 * Triggers deprecation messages (E_USER_DEPRECATED), each message once per
 * request. TYPO3 writes them to the deprecation log if it is enabled.
 */
final class DeprecationUtility
{
    public const ISSUE_URL = 'https://github.com/dwenzel/t3import_export/issues/75';
    public const FILE_COMPONENTS_ISSUE_URL = 'https://github.com/dwenzel/t3import_export/issues/144';
    public const CORE_ISSUE_URL = 'https://github.com/dwenzel/t3import_export/issues/71';
    public const DATABASE_ISSUE_URL = 'https://github.com/dwenzel/t3import_export/issues/132';
    public const XML_COMPONENTS_ISSUE_URL = 'https://github.com/dwenzel/t3import_export/issues/154';

    /**
     * @var array<string, true>
     */
    private static array $triggered = [];

    public static function trigger(string $message, string $issueUrl = self::ISSUE_URL): void
    {
        if (isset(self::$triggered[$message])) {
            return;
        }
        self::$triggered[$message] = true;
        trigger_error(
            't3import_export: ' . $message . ' See ' . $issueUrl,
            E_USER_DEPRECATED
        );
    }

    /**
     * Only for tests
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$triggered = [];
    }
}
