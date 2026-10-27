<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Display;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Mezzio\MenuPages;
use Contenir\Navigation\Mezzio\RecordLink;
use Contenir\Navigation\Mezzio\RecordLinks;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;

#[Group('unit')]
final class MenuPagesTest extends TestCase
{
    #[Test]
    public function describesAnAddressAsAUriPageAsTyped(): void
    {
        $item = Item::url(Url::parse('https://instagram.com/studioesteta'), 'Instagram')
            ->withPresentation('Instagram', Display::IconLabel, 'instagram', '_blank', 'nav__social');

        static::assertSame(
            [[
                'type'    => 'uri',
                'label'   => 'Instagram',
                'uri'     => 'https://instagram.com/studioesteta',
                'target'  => '_blank',
                'class'   => 'nav__social',
                'kind'    => 'url',
                'display' => 'icon_label',
                'icon'    => 'instagram',
                'pages'   => [],
            ]],
            MenuPages::of([new Node($item)], RecordLinks::none()),
        );
    }

    #[Test]
    public function describesARecordAtItsLinkWithTheItemsFragment(): void
    {
        $links = RecordLinks::of(['site.page.home:12' => new RecordLink('/about', 'About us')]);

        static::assertSame(
            ['/about#team'],
            array_column(MenuPages::of(
                [new Node(Item::record(new RecordTarget('site.page.home', 12), fragment: 'team'))],
                $links,
            ), 'uri'),
        );
    }

    #[Test]
    public function describesATextItemWithNoAddressAndItsHtml(): void
    {
        static::assertSame(
            [[
                'type'    => 'uri',
                'label'   => 'Studio',
                'uri'     => null,
                'target'  => null,
                'class'   => null,
                'kind'    => 'text',
                'display' => 'label',
                'html'    => '<strong>Studio</strong>',
                'pages'   => [],
            ]],
            MenuPages::of([new Node(Item::text('Studio', '<strong>Studio</strong>'))], RecordLinks::none()),
        );
    }

    #[Test]
    public function labelsARecordByTheItemsLabel(): void
    {
        $links = RecordLinks::of(['site.page.home:12' => new RecordLink('/about', 'About us')]);

        static::assertSame(['Studio'], array_column(MenuPages::of(
            [new Node(Item::record(new RecordTarget('site.page.home', 12), label: 'Studio'))],
            $links,
        ), 'label'));
    }

    #[Test]
    public function labelsARecordWithNoLabelByItsTitle(): void
    {
        $links = RecordLinks::of(['site.page.home:12' => new RecordLink('/about', 'About us')]);

        static::assertSame(
            [[
                'type'    => 'uri',
                'label'   => 'About us',
                'uri'     => '/about',
                'target'  => null,
                'class'   => null,
                'kind'    => 'record',
                'display' => 'label',
                'pages'   => [],
            ]],
            MenuPages::of([new Node(Item::record(new RecordTarget('site.page.home', 12)))], $links),
        );
    }

    #[Test]
    public function leavesOutAnItemSwitchedOff(): void
    {
        $hidden = Item::url(Url::parse('/press'), 'Press')->withPresentation('Press', active: false);

        static::assertSame(
            ['Work'],
            array_column(MenuPages::of([
                new Node($hidden),
                new Node(Item::url(Url::parse('/work'), 'Work')),
            ], RecordLinks::none()), 'label'),
        );
    }

    #[Test]
    public function leavesOutARecordWithNoLinkAndEverythingUnderIt(): void
    {
        static::assertSame([], MenuPages::of([
            new Node(Item::record(new RecordTarget('site.page.home', 12)), [
                new Node(Item::url(Url::parse('/press'), 'Press')),
            ]),
        ], RecordLinks::none()));
    }

    #[Test]
    public function nestsTheItemsUnderAnItemAsItsPages(): void
    {
        $pages = MenuPages::of([
            new Node(Item::text('Studio'), [
                new Node(Item::url(Url::parse('/about'), 'About')),
                new Node(Item::url(Url::parse('/team'), 'Team')),
            ]),
        ], RecordLinks::none());

        static::assertSame(['About', 'Team'], array_column($pages[0]['pages'] ?? [], 'label'));
    }
}
