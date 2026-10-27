<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\View;

use Contenir\Navigation\Mezzio\MenuContainers;
use Laminas\Navigation\Navigation;

/**
 * The live menus in a laminas-view template.
 *
 * @example <?php foreach ($this->menus()->starting('banner') as $menu): ?>
 * @example <?php $footer = $this->menus()->get('footer'); ?>
 *
 * @api
 */
final readonly class Menus
{
    public function __construct(
        private MenuContainers $containers,
    ) {}

    /**
     * The live menu with $handle, or null while none is published.
     */
    public function get(string $handle): ?Navigation
    {
        return $this->containers->get($handle);
    }

    /**
     * The live menus whose handles start with $prefix, in menu order, leaving out those with
     * nothing to show.
     *
     * @return list<Navigation>
     */
    public function starting(string $prefix): array
    {
        return $this->containers->starting($prefix);
    }

    public function __invoke(): self
    {
        return $this;
    }
}
