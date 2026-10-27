<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Mezzio\MenuContainers;
use Contenir\Navigation\Mezzio\RecordLink;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Cache\InMemoryCache;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Record\FixedRecordLinks;
use Contenir\Navigation\Mezzio\Tests\Trait\BuildsMenusTrait;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Laminas\Navigation\Navigation as Container;
use Laminas\Navigation\Page\AbstractPage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_keys;
use function array_map;

#[Group('unit')]
final class MenuContainersTest extends TestCase
{
    use BuildsMenusTrait;

    /**
     * Each top-level page's address, by its label.
     *
     * @param Container<AbstractPage>|null $container
     *
     * @return array<string, string>
     */
    private static function hrefs(?Container $container): array
    {
        $hrefs = [];
        foreach ($container ?? new Container() as $page) {
            $hrefs[(string) $page->getLabel()] = $page->getHref();
        }

        return $hrefs;
    }

    #[Test]
    public function addressesTheRecordsThroughTheResolver(): void
    {
        $about      = new RecordTarget('site.page.home', 12);
        $containers = $this->containers(
            $this->menus(
                [new Navigation('footer', 'Footer', id: 1, revisionId: 5)],
                [5 => [new Node(Item::record($about))]],
            ),
            new FixedRecordLinks(['site.page.home:12' => new RecordLink('/about', 'About')]),
        );

        static::assertSame(['About' => '/about'], self::hrefs($containers->get('footer')));
    }

    #[Test]
    public function buildsTheLiveMenuWithAHandleAsAContainer(): void
    {
        $containers = $this->containers($this->menus(
            [new Navigation('footer', 'Footer', id: 1, revisionId: 5)],
            [5 => [new Node(Item::url(Url::parse('/privacy'), 'Privacy'))]],
        ));

        static::assertSame(['Privacy' => '/privacy'], self::hrefs($containers->get('footer')));
    }

    #[Test]
    public function drawsAMenuKeptInTheCacheWithoutResolvingItsRecords(): void
    {
        $records    = new FixedRecordLinks();
        $containers = $this->containers(
            $this->menus([new Navigation('footer', 'Footer', id: 1, revisionId: 5)]),
            $records,
            new InMemoryCache([
                'contenir_navigation_footer_5' => [
                    ['type' => 'uri', 'label' => 'Kept', 'uri' => '/kept'],
                ],
            ]),
        );

        static::assertSame([['Kept' => '/kept'], []], [self::hrefs($containers->get('footer')), $records->asked]);
    }

    #[Test]
    public function hasNoMenuForAHandleNotYetPublished(): void
    {
        $containers = $this->containers($this->menus([new Navigation('footer', 'Footer', id: 1)]));

        static::assertNull($containers->get('footer'));
    }

    #[Test]
    public function hasNoMenuWhenItIsDeletedAsItIsRead(): void
    {
        $repository = $this->createStub(NavigationRepositoryInterface::class);
        $repository->method('liveRevisionId')->willReturn(5);
        $repository->method('navigationByHandle')->willReturn(null);

        static::assertNull($this->containers($repository)->get('footer'));
    }

    #[Test]
    public function hasNoMenuWhenItsLiveRevisionIsGone(): void
    {
        $containers = $this->containers($this->menus([new Navigation('footer', 'Footer', id: 1, revisionId: 5)]));

        static::assertNull($containers->get('footer'));
    }

    #[Test]
    public function hasNoMenuWhenTheLiveMenuCannotBeRead(): void
    {
        $repository = $this->createStub(NavigationRepositoryInterface::class);
        $repository->method('liveRevisionId')->willReturn(5);
        $repository->method('navigationByHandle')
            ->willThrowException(StorageException::failed(new RuntimeException()));

        static::assertNull($this->containers($repository)->get('footer'));
    }

    #[Test]
    public function hasNoMenuWhereTheNavigationTablesAreMissing(): void
    {
        $repository = $this->createStub(NavigationRepositoryInterface::class);
        $repository->method('liveRevisionId')->willThrowException(StorageException::failed(new RuntimeException()));

        static::assertNull($this->containers($repository)->get('footer'));
    }

    #[Test]
    public function keepsAMenuBuiltInTheCacheUnderItsRevision(): void
    {
        $cache = new InMemoryCache();
        $this->containers($this->menus(
            [new Navigation('footer', 'Footer', id: 1, revisionId: 5)],
            [5 => [new Node(Item::url(Url::parse('/privacy'), 'Privacy'))]],
        ), cache: $cache)->get('footer');

        static::assertSame(['contenir_navigation_footer_5'], array_keys($cache->items));
    }

    #[Test]
    public function leavesOutAMenuNotYetPublishedFromThoseStartingWithAPrefix(): void
    {
        $containers = $this->containers($this->menus(
            [
                new Navigation('banner-left', 'Banner Left', id: 1),
                new Navigation('banner-right', 'Banner Right', id: 2, revisionId: 6),
            ],
            [6 => [new Node(Item::url(Url::parse('/press'), 'Press'))]],
        ));

        static::assertSame([['Press' => '/press']], array_map(self::hrefs(...), $containers->starting('banner')));
    }

    #[Test]
    public function leavesOutAMenuWithNothingToShowFromThoseStartingWithAPrefix(): void
    {
        $containers = $this->containers($this->menus(
            [
                new Navigation('banner-left', 'Banner Left', id: 1, revisionId: 5),
                new Navigation('banner-right', 'Banner Right', id: 2, revisionId: 6),
            ],
            [5 => [], 6 => [new Node(Item::url(Url::parse('/press'), 'Press'))]],
        ));

        static::assertSame([['Press' => '/press']], array_map(self::hrefs(...), $containers->starting('banner')));
    }

    #[Test]
    public function listsAHandleTwoMenusShareOnce(): void
    {
        $containers = $this->containers($this->menus(
            [
                new Navigation('banner-left', 'Banner Left', id: 1, revisionId: 5),
                new Navigation('banner-left', 'Banner Left copy', id: 2, revisionId: 6),
            ],
            [
                5 => [new Node(Item::url(Url::parse('/work'), 'Work'))],
                6 => [new Node(Item::url(Url::parse('/press'), 'Press'))],
            ],
        ));

        static::assertSame([['Work' => '/work']], array_map(self::hrefs(...), $containers->starting('banner')));
    }

    #[Test]
    public function listsNoMenusWhereTheNavigationTablesAreMissing(): void
    {
        $repository = $this->createStub(NavigationRepositoryInterface::class);
        $repository->method('navigations')->willThrowException(StorageException::failed(new RuntimeException()));

        static::assertSame([], $this->containers($repository)->starting('banner'));
    }

    #[Test]
    public function listsTheLiveMenusStartingWithAPrefixInMenuOrder(): void
    {
        $containers = $this->containers($this->menus(
            [
                new Navigation('banner-left', 'Banner Left', id: 1, revisionId: 5),
                new Navigation('footer', 'Footer', id: 2, revisionId: 6),
                new Navigation('banner-right', 'Banner Right', id: 3, revisionId: 7),
            ],
            [
                5 => [new Node(Item::url(Url::parse('/work'), 'Work'))],
                6 => [new Node(Item::url(Url::parse('/privacy'), 'Privacy'))],
                7 => [new Node(Item::url(Url::parse('/press'), 'Press'))],
            ],
        ));

        static::assertSame(
            [['Work' => '/work'], ['Press' => '/press']],
            array_map(self::hrefs(...), $containers->starting('banner')),
        );
    }

    #[Test]
    public function resolvesTheRecordsOfAMenuOnceForAllItsItems(): void
    {
        $about   = new RecordTarget('site.page.home', 12);
        $team    = new RecordTarget('site.page.home', 13);
        $records = new FixedRecordLinks();
        $this->containers(
            $this->menus(
                [new Navigation('footer', 'Footer', id: 1, revisionId: 5)],
                [5 => [new Node(Item::record($about), [new Node(Item::record($team))])]],
            ),
            $records,
        )->get('footer');

        static::assertSame([[$about, $team]], $records->asked);
    }

    private function containers(
        NavigationRepositoryInterface $repository,
        FixedRecordLinks $records = new FixedRecordLinks(),
        ?InMemoryCache $cache = null,
    ): MenuContainers {
        return new MenuContainers($this->publisher($repository), $repository, $records, $cache);
    }
}
