<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

/**
 * Where a menu's record item links to, and the title it is shown by when the item has no label.
 *
 * @api
 */
final readonly class RecordLink
{
    public function __construct(
        public string $href,
        public string $title,
    ) {}
}
