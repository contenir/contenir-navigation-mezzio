<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit\View;

use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Mezzio\MenuContainers;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Record\FixedRecordLinks;
use Contenir\Navigation\Mezzio\Tests\Trait\BuildsMenusTrait;
use Contenir\Navigation\Mezzio\View\Menus;
use Contenir\Navigation\Mezzio\View\MenusFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MenusTest extends TestCase
{
    use BuildsMenusTrait;

    #[Test]
    public function factoryReadsTheMenuContainers(): void
    {
        $menus = (new MenusFactory())(new InMemoryContainer([MenuContainers::class => $this->containers()]));

        static::assertCount(2, $menus->starting('banner'));
    }

    #[Test]
    public function givesItselfWhenCalledFromATemplate(): void
    {
        $menus = new Menus($this->containers());

        static::assertSame($menus, $menus());
    }

    #[Test]
    public function givesTheLiveMenuWithAHandle(): void
    {
        static::assertCount(1, (new Menus($this->containers()))->get('banner-right') ?? []);
    }

    #[Test]
    public function listsTheLiveMenusStartingWithAPrefix(): void
    {
        static::assertCount(2, (new Menus($this->containers()))->starting('banner'));
    }

    private function containers(): MenuContainers
    {
        $repository = $this->menus(
            [
                new Navigation('banner-left', 'Banner Left', id: 1, revisionId: 5),
                new Navigation('banner-right', 'Banner Right', id: 2, revisionId: 6),
            ],
            [
                5 => [new Node(Item::url(Url::parse('/work'), 'Work'))],
                6 => [new Node(Item::url(Url::parse('/press'), 'Press'))],
            ],
        );

        return new MenuContainers($this->publisher($repository), $repository, new FixedRecordLinks());
    }
}
