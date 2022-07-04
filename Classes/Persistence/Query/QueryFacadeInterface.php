<?php
/**
 * This file is part of the johanniter Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * README.md file that was distributed with this source code.
 */

namespace CPSIT\T3importExport\Persistence\Query;

interface QueryFacadeInterface
{
    public function getQueryResultByConfig(array $queryConfiguration): array;
}