<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Menu\Node;

/**
 * The records a live menu's switched-on items link to, nested ones included, for the resolver
 * to read at once. The items under one switched off are left out with it.
 *
 * @internal
 */
final readonly class MenuRecords
{
    /**
     * @param list<Node> $nodes
     *
     * @return list<RecordTarget>
     */
    public static function of(array $nodes): array
    {
        $records = [];
        foreach ($nodes as $node) {
            if (! $node->item->active) {
                continue;
            }

            if (null !== $node->item->record) {
                $records[] = $node->item->record;
            }

            foreach (self::of($node->children) as $record) {
                $records[] = $record;
            }
        }

        return $records;
    }
}
