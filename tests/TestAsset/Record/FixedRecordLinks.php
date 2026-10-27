<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\TestAsset\Record;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Mezzio\RecordLink;
use Contenir\Navigation\Mezzio\RecordLinkResolverInterface;
use Contenir\Navigation\Mezzio\RecordLinks;
use Override;

/**
 * A resolver with a fixed link for each record it was given, by section and id, that notes each
 * list of records it was asked for.
 */
final class FixedRecordLinks implements RecordLinkResolverInterface
{
    /** @var list<list<RecordTarget>> */
    public array $asked = [];

    /**
     * @param array<string, RecordLink> $links
     */
    public function __construct(
        private readonly array $links = [],
    ) {}

    #[Override]
    public function resolve(array $records): RecordLinks
    {
        $this->asked[] = $records;

        return RecordLinks::of($this->links);
    }
}
