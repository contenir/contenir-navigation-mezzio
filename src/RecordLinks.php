<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Navigation\Link\RecordTarget;

/**
 * The links a resolver found for a menu's records, looked up by the record each item targets.
 * A record it found nothing published for, or no address for, has no link.
 *
 * @api
 */
final readonly class RecordLinks
{
    /**
     * @param array<string, RecordLink> $links By section and id, as key() gives them.
     */
    private function __construct(
        private array $links,
    ) {}

    /**
     * How $record is looked up: its section and id, joined by a colon.
     */
    public static function key(RecordTarget $record): string
    {
        return "{$record->section}:{$record->id}";
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * @param array<string, RecordLink> $links By section and id, as key() gives them.
     */
    public static function of(array $links): self
    {
        return new self($links);
    }

    public function for(RecordTarget $record): ?RecordLink
    {
        return $this->links[self::key($record)] ?? null;
    }
}
