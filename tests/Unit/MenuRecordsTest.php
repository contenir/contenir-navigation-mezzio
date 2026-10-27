<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Mezzio\MenuRecords;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MenuRecordsTest extends TestCase
{
    #[Test]
    public function leavesOutAnItemSwitchedOffAndEverythingUnderIt(): void
    {
        $hidden = Item::record(new RecordTarget('site.page.home', 12))->withPresentation(null, active: false);

        $work = new RecordTarget('site.page.home', 11);

        static::assertSame(
            [$work],
            MenuRecords::of([
                new Node($hidden, [new Node(Item::record(new RecordTarget('site.page.home', 13)))]),
                new Node(Item::record($work)),
            ]),
        );
    }

    #[Test]
    public function listsTheRecordsOfEveryLevelInMenuOrder(): void
    {
        $about = new RecordTarget('site.page.home', 12);
        $team  = new RecordTarget('site.page.home', 13);
        $work  = new RecordTarget('site.page.home', 11);

        static::assertSame(
            [$about, $team, $work],
            MenuRecords::of([
                new Node(Item::record($about), [
                    new Node(Item::url(Url::parse('/press'), 'Press')),
                    new Node(Item::record($team)),
                ]),
                new Node(Item::record($work)),
            ]),
        );
    }
}
