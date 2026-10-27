<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function is_array;

/**
 * Where the menus' pages are kept, by handle and live revision, when the site gives a cache.
 *
 * @internal
 */
final readonly class MenuCache
{
    /** Starts each menu's cache key. */
    public const string KEY_PREFIX = 'contenir_navigation_';

    public function __construct(
        private ?CacheInterface $cache,
    ) {}

    private static function key(string $handle, int $revisionId): string
    {
        return self::KEY_PREFIX . "{$handle}_{$revisionId}";
    }

    /**
     * The pages kept for $handle at $revisionId, or null when none are.
     *
     * @return list<array<string, mixed>>|null
     */
    public function get(string $handle, int $revisionId): ?array
    {
        try {
            /** @var mixed $pages */
            $pages = $this->cache?->get(self::key($handle, $revisionId));
        } catch (InvalidArgumentException) {
            return null;
        }

        /** @var list<array<string, mixed>>|null The menu's pages, as set() kept them. */
        return is_array($pages) ? $pages : null;
    }

    /**
     * Keeps $pages for $handle at $revisionId, and says whether they were kept.
     *
     * @param list<array<string, mixed>> $pages
     */
    public function set(string $handle, int $revisionId, array $pages): bool
    {
        try {
            return $this->cache?->set(self::key($handle, $revisionId), $pages) ?? false;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
