<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Navigation\Link\RecordTarget;

/**
 * Turns the records a menu links to into the addresses and titles the site shows them by.
 *
 * A site registers its own under this interface to address records in some other way than
 * WorkflowRecordLinks does.
 *
 * @api
 */
interface RecordLinkResolverInterface
{
    /**
     * The links for $records, all at once, so a site can read them in one query.
     *
     * @param list<RecordTarget> $records
     */
    public function resolve(array $records): RecordLinks;
}
