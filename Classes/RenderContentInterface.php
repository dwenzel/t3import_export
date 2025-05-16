<?php

declare(strict_types=1);

namespace CPSIT\T3importExport;

/**
 * Interface RenderContentInterface
 *
 * Provides methods for rendering TypoScript content
 */
interface RenderContentInterface
{
    /**
     * @return mixed Rendered Content
     */
    public function renderContent(array $record, array $configuration);
}
