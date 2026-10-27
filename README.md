# contenir/contenir-navigation-mezzio

[![Continuous Integration](https://github.com/contenir/contenir-navigation-mezzio/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-navigation-mezzio/actions/workflows/continuous-integration.yml)

Draws a Contenir site's managed menus in Mezzio. Each menu editors build and publish in the
CMS's Navigation section ([`contenir/contenir-navigation`](https://github.com/contenir/contenir-navigation))
becomes a [Laminas Navigation](https://docs.laminas.dev/laminas-navigation/) container, with the
records it links to addressed through the router, and kept in a cache by its live revision.

## Requirements

- PHP 8.3, 8.4 or 8.5
- `contenir/contenir-navigation` 0.2+, `laminas/laminas-navigation` 2.23+, `mezzio/mezzio-router` 3.x
- For `WorkflowRecordLinks`: `contenir/contenir-resource-mezzio` and `contenir/contenir-workflow-mezzio` 2.2+
- Optional: `laminas/laminas-cache`, to keep the menus in a laminas-cache storage

## Install

```bash
composer require contenir/contenir-navigation-mezzio
```

`laminas/laminas-component-installer` adds `Contenir\Navigation\Mezzio\ConfigProvider` to
`config/config.php`; without it, add the provider yourself.

## Configure

```php
return [
    'contenir_navigation_mezzio' => [
        // The store the menus are kept in: a PSR-16 cache or a laminas-cache storage service.
        // Leave it out to build the menus on every request.
        'cache'    => 'menus',

        // The CMS sections editors link records from, and the resource type each edits.
        'sections' => [
            'site.page.home'           => 'page',
            'library.projects.article' => 'project',
        ],
    ],
    'pagecache' => [
        // The CMS's cache clear empties the menus with the pages.
        'clear' => ['caches' => ['menus']],
    ],
];
```

A menu is kept under its handle and live revision, so publishing moves it to a new key at once.
A record's new title or address reaches the menu when the CMS clears the site's caches, so list
the store in `pagecache.clear.caches`
([`contenir/contenir-page-cache-mezzio`](https://github.com/contenir/contenir-page-cache-mezzio)).

## Draw

```php
<?php foreach ($this->menus()->starting('banner') as $menu): ?>
    <ul class="nav">
        <?php foreach ($menu as $page): ?>
            <li>
                <?php if (null === $page->getUri()): ?>
                    <span class="nav__text"><?= $this->escapeHtml($page->getLabel()) ?></span>
                <?php else: ?>
                    <a class="nav__link" href="<?= $this->escapeHtmlAttr($page->getHref()) ?>"
                        <?= $page->getTarget() ? 'target="' . $this->escapeHtmlAttr($page->getTarget()) . '" rel="noopener"' : '' ?>>
                        <?= $this->escapeHtml($page->getLabel()) ?>
                    </a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php endforeach ?>
```

`$this->menus()->get('footer')` gives one menu by its handle, or null while none is published.
`starting('banner')` gives every live menu whose handle starts with `banner`, in the order the
menus are arranged in the CMS, each handle once, leaving out those with nothing to show.

Each page is a Laminas `Uri` page:

| Property  | From the item |
|-----------|---------------|
| `label`   | Its label, or else the record's title |
| `uri`     | The record's address plus the item's fragment, the address as typed, or null for text |
| `target`, `class` | As set in the CMS |
| `kind`    | `record`, `url` or `text` |
| `display` | `label`, `icon` or `icon_label` |
| `icon`, `html` | Where the item has them |
| `pages`   | The items nested under it |

Items switched off are left out with everything under them, and so is a record that isn't
published or whose workflow gives it no route.

## Records

`WorkflowRecordLinks` reads a menu's records in one query from the published resources of the
types the sections map, and builds each one's address from its workflow's route through the
router, as the site routes it. To address records some other way, register your own
`RecordLinkResolverInterface`.

## Development

```bash
composer check
```

runs the formatter and linter, static analysis, the unit and integration suites, and mutation
testing, which needs an MSI of 100%.
