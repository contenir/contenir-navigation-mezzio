<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use InvalidArgumentException;

use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

/**
 * The package's settings, from `config['contenir_navigation_mezzio']`:
 *
 * - `cache`: the service name of the store the menus are kept in, a PSR-16 cache or a
 *   laminas-cache storage; none builds them on every request. List it in the site's
 *   `pagecache.clear.caches` so the CMS's cache clear empties it.
 * - `sections`: for WorkflowRecordLinks, the resource type each CMS section editors link
 *   records from edits, such as `['site.page.home' => 'page']`.
 *
 * @internal
 */
final readonly class MenuOptions
{
    public const string KEY = 'contenir_navigation_mezzio';

    /**
     * @param array<string, string> $sections
     */
    private function __construct(
        public ?string $cache,
        public array $sections,
    ) {}

    /**
     * @param array<array-key, mixed> $config The application's configuration.
     *
     * @throws InvalidArgumentException When a setting is not of the kind it needs to be.
     */
    public static function fromAppConfig(array $config): self
    {
        /** @var mixed $options */
        $options = $config[self::KEY] ?? [];
        if (! is_array($options)) {
            throw self::invalid(self::KEY, 'an array', $options);
        }

        /** @var mixed $cache */
        $cache = $options['cache'] ?? null;
        if (null !== $cache && (! is_string($cache) || '' === $cache)) {
            throw self::invalid(self::KEY . '.cache', 'a service name', $cache);
        }

        return new self($cache, self::sections($options['sections'] ?? []));
    }

    private static function invalid(string $setting, string $expected, mixed $value): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'contenir/contenir-navigation-mezzio: %s must be %s, not %s.',
            $setting,
            $expected,
            get_debug_type($value),
        ));
    }

    /**
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    private static function sections(mixed $sections): array
    {
        if (! is_array($sections)) {
            throw self::invalid(self::KEY . '.sections', 'a map of CMS sections to resource types', $sections);
        }

        $types = [];
        /** @var mixed $type */
        foreach ($sections as $section => $type) {
            if (! is_string($section) || ! is_string($type) || '' === $type) {
                throw self::invalid(self::KEY . '.sections', 'a map of CMS sections to resource types', $sections);
            }

            $types[$section] = $type;
        }

        return $types;
    }
}
