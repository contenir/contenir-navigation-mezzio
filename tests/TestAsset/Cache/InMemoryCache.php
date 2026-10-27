<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\TestAsset\Cache;

use Override;
use Psr\SimpleCache\CacheInterface;

use function array_key_exists;

/**
 * An in-memory PSR-16 cache, its items open for a test to read and plant.
 *
 * Parameters are untyped so the double fits psr/simple-cache 1, 2 and 3 alike.
 */
final class InMemoryCache implements CacheInterface
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(
        public array $items = [],
    ) {}

    #[Override]
    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    #[Override]
    public function delete($key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    #[Override]
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }

        return true;
    }

    #[Override]
    public function get($key, $default = null): mixed
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
    }

    /**
     * @return iterable<string, mixed>
     */
    #[Override]
    public function getMultiple($keys, $default = null): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->get($key, $default);
        }

        return $items;
    }

    #[Override]
    public function has($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    #[Override]
    public function set($key, $value, $ttl = null): bool
    {
        $this->items[$key] = $value;

        return true;
    }

    #[Override]
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->items[(string) $key] = $value;
        }

        return true;
    }
}
