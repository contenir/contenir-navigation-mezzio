# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `MenuContainers`: each live menu from contenir/contenir-navigation as a Laminas Navigation
  container, by handle (`get()`), or every live menu whose handle starts with a prefix, in menu
  order (`starting()`). Each item switched on is a `uri` page with its nested items as its pages,
  carrying its `kind`, `display`, `icon` and `html`; a record with no link is left out with
  everything under it.
- `RecordLinkResolverInterface`, and `WorkflowRecordLinks` for Contenir sites: a menu's records
  read in one query from the published resources of the types `contenir_navigation_mezzio.sections`
  maps their sections to, at the addresses their workflows route them at.
- The menus kept in the cache `contenir_navigation_mezzio.cache` names, a PSR-16 cache or a
  laminas-cache storage, by handle and live revision. List it in `pagecache.clear.caches` so the
  CMS's cache clear empties it.
- The `menus()` view helper for laminas-view.
