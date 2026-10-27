<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Mezzio\RecordLink;
use Contenir\Navigation\Mezzio\RecordLinks;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordLinksTest extends TestCase
{
    #[Test]
    public function findsTheLinkForARecordBySectionAndId(): void
    {
        $link = new RecordLink('/about', 'About');

        static::assertSame(
            $link,
            RecordLinks::of(['site.page.home:12' => $link])->for(new RecordTarget('site.page.home', '12')),
        );
    }

    #[Test]
    public function hasNoLinkForARecordOfAnotherSection(): void
    {
        $links = RecordLinks::of(['site.page.home:12' => new RecordLink('/about', 'About')]);

        static::assertNull($links->for(new RecordTarget('library.projects.article', 12)));
    }

    #[Test]
    public function keysARecordBySectionAndId(): void
    {
        static::assertSame('site.page.home:12', RecordLinks::key(new RecordTarget('site.page.home', 12)));
    }

    #[Test]
    public function noneHasNoLinks(): void
    {
        static::assertNull(RecordLinks::none()->for(new RecordTarget('site.page.home', 12)));
    }
}
