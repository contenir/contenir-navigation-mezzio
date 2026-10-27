<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Laminas\Navigation\Navigation;
use Psr\SimpleCache\CacheInterface;

use function str_starts_with;

/**
 * The site's live menus as Laminas Navigation containers, one per menu handle.
 *
 * A menu is the one the site shows for its handle: the first published menu with it, in menu
 * order, at its live revision. Its pages are built as MenuPages describes, with the records its
 * items link to resolved all at once, and kept in the cache, when one is given, under the handle
 * and the live revision. Publishing a revision so moves the menu to a new key; the site's cache
 * clear empties the rest, such as a record's new title or address.
 *
 * Where the navigation tables are not there yet, or the menu cannot be read, there is no menu,
 * so the site draws what it would without one.
 *
 * @api
 */
final readonly class MenuContainers
{
    private MenuCache $pages;

    public function __construct(
        private Publisher $publisher,
        private NavigationRepositoryInterface $menus,
        private RecordLinkResolverInterface $records,
        ?CacheInterface $cache = null,
    ) {
        $this->pages = new MenuCache($cache);
    }

    /**
     * The live menu with $handle, or null while none is published.
     */
    public function get(string $handle): ?Navigation
    {
        try {
            $revisionId = $this->menus->liveRevisionId($handle);
        } catch (StorageException) {
            return null;
        }

        if (null === $revisionId) {
            return null;
        }

        $pages = $this->pages->get($handle, $revisionId);
        if (null === $pages) {
            $pages = $this->build($handle);
            if (null === $pages) {
                return null;
            }

            $this->pages->set($handle, $revisionId, $pages);
        }

        return new Navigation($pages);
    }

    /**
     * The live menus whose handles start with $prefix, such as `banner` for banner-left and
     * banner-right, in menu order, each handle once, leaving out those with nothing to show.
     *
     * @return list<Navigation>
     */
    public function starting(string $prefix): array
    {
        try {
            $menus = $this->menus->navigations();
        } catch (StorageException) {
            return [];
        }

        $handles = [];
        foreach ($menus as $menu) {
            if (! str_starts_with($menu->handle, $prefix)) {
                continue;
            }

            $handles[$menu->handle] = $menu->handle;
        }

        $shown = [];
        foreach ($handles as $handle) {
            $container = $this->get($handle);
            if (null === $container || ! $container->hasPages()) {
                continue;
            }

            $shown[] = $container;
        }

        return $shown;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function build(string $handle): ?array
    {
        try {
            $live = $this->publisher->live($handle);
        } catch (NotFoundException|StorageException) {
            return null;
        }

        if (null === $live) {
            return null;
        }

        $nodes = $live->nodes();

        return MenuPages::of($nodes, $this->records->resolve(MenuRecords::of($nodes)));
    }
}
