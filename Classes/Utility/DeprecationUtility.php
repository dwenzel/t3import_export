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

    /**
     * @var array<string, true>
     */
    private static array $triggered = [];

    public static function trigger(string $message): void
    {
        if (isset(self::$triggered[$message])) {
            return;
        }
        self::$triggered[$message] = true;
        trigger_error(
            't3import_export: ' . $message . ' See ' . self::ISSUE_URL,
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
