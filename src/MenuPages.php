<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\ItemKind;
use Contenir\Navigation\Menu\Node;

/**
 * A live menu's items as the page specifications a Laminas Navigation container is built from.
 *
 * Each item that is switched on becomes a `uri` page, with the items nested under it as its
 * pages: a record at the address its link gives it, plus the item's fragment, labelled by the
 * item's label or else the record's title; an address as typed; and a text item with no address.
 * Each page carries its item's `kind`, `display`, and its `icon` and `html` where it has them.
 * An item switched off, and a record with no link, are left out with everything under them.
 *
 * @internal
 *
 * @psalm-type PageSpec = array{
 *     type: 'uri',
 *     label: string,
 *     uri: string|null,
 *     target: string|null,
 *     class: string|null,
 *     kind: string,
 *     display: string,
 *     icon?: string,
 *     html?: string,
 *     pages: list<array<string, mixed>>,
 * }
 */
final readonly class MenuPages
{
    /**
     * @param list<Node> $nodes
     *
     * @return list<PageSpec>
     */
    public static function of(array $nodes, RecordLinks $links): array
    {
        $pages = [];
        foreach ($nodes as $node) {
            $page = $node->item->active ? self::page($node, $links) : null;
            if (null !== $page) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    private static function fragment(Item $item): string
    {
        return null === $item->fragment ? '' : "#{$item->fragment}";
    }

    /**
     * @return PageSpec|null
     */
    private static function page(Node $node, RecordLinks $links): ?array
    {
        $item = $node->item;
        $link = null === $item->record ? null : $links->for($item->record);
        if (ItemKind::Record === $item->kind && null === $link) {
            return null;
        }

        $page = [
            'type'    => 'uri',
            'label'   => $item->label ?? $link->title ?? '',
            'uri'     => null === $link ? $item->url?->href : $link->href . self::fragment($item),
            'target'  => $item->target,
            'class'   => $item->class,
            'kind'    => $item->kind->value,
            'display' => $item->display->value,
        ];
        if (null !== $item->icon) {
            $page['icon'] = $item->icon;
        }

        if (null !== $item->html) {
            $page['html'] = $item->html;
        }

        $page['pages'] = self::of($node->children, $links);

        return $page;
    }
}
