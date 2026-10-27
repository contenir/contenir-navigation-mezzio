<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Mezzio\MenuCache;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Cache\InMemoryCache;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Cache\KeyRefusingCache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MenuCacheTest extends TestCase
{
    private const array PAGES = [['type' => 'uri', 'label' => 'Work', 'uri' => '/work']];

    #[Test]
    public function hasNothingKeptForAnotherRevision(): void
    {
        $cache = new MenuCache(new InMemoryCache(['contenir_navigation_banner-left_5' => self::PAGES]));

        static::assertNull($cache->get('banner-left', 6));
    }

    #[Test]
    public function keepsAMenusPagesByHandleAndRevision(): void
    {
        $store = new InMemoryCache();
        $kept  = (new MenuCache($store))->set('banner-left', 5, self::PAGES);

        static::assertSame([true, ['contenir_navigation_banner-left_5' => self::PAGES]], [$kept, $store->items]);
    }

    #[Test]
    public function keepsNothingUnderAKeyTheCacheRefuses(): void
    {
        static::assertFalse((new MenuCache(new KeyRefusingCache()))->set('banner-left', 5, self::PAGES));
    }

    #[Test]
    public function keepsNothingWithoutACache(): void
    {
        $cache = new MenuCache(null);

        static::assertSame([false, null], [$cache->set('banner-left', 5, self::PAGES), $cache->get('banner-left', 5)]);
    }

    #[Test]
    public function readsBackThePagesKeptForAHandleAndRevision(): void
    {
        $cache = new MenuCache(new InMemoryCache(['contenir_navigation_banner-left_5' => self::PAGES]));

        static::assertSame(self::PAGES, $cache->get('banner-left', 5));
    }

    #[Test]
    public function takesAKeyTheCacheRefusesAsNothingKept(): void
    {
        static::assertNull((new MenuCache(new KeyRefusingCache()))->get('banner-left', 5));
    }

    #[Test]
    public function takesSomethingOtherThanPagesAsNothingKept(): void
    {
        $cache = new MenuCache(new InMemoryCache(['contenir_navigation_banner-left_5' => 'stale']));

        static::assertNull($cache->get('banner-left', 5));
    }
}
