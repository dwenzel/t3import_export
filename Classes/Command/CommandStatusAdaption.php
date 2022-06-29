<?php
/**
 * This file is part of the johanniter Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * README.md file that was distributed with this source code.
 */

namespace CPSIT\T3importExport\Command;

use Symfony\Component\Console\Command\Command;

/**
 * @codeCoverageIgnore
 */
class CommandStatusAdaption
{
    protected const INVALID = 2;
    protected const FAILURE = 1;
    protected const SUCCESS = 0;

    public function getCommandStatusInvalid(): int
    {
        return defined(Command::class . '::INVALID') ? Command::INVALID : self::INVALID;
    }

    public function getCommandStatusFailure(): int
    {
        return defined(Command::class . '::FAILURE') ? Command::FAILURE : self::FAILURE;
    }

     public function getCommandStatusSuccess(): int
    {
        return defined(Command::class . '::SUCCESS') ? Command::SUCCESS : self::SUCCESS;
    }
}