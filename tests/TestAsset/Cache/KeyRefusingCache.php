<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\TestAsset\Cache;

use Override;
use Psr\SimpleCache\CacheInterface;

/**
 * A cache that refuses every key, as a backend with a stricter key pattern than PSR-16's does.
 */
final class KeyRefusingCache implements CacheInterface
{
    #[Override]
    public function clear(): bool
    {
        return true;
    }

    #[Override]
    public function delete($key): bool
    {
        throw new InvalidKey("The key {$key} is refused.");
    }

    #[Override]
    public function deleteMultiple($keys): bool
    {
        throw new InvalidKey('The keys are refused.');
    }

    #[Override]
    public function get($key, $default = null): mixed
    {
        throw new InvalidKey("The key {$key} is refused.");
    }

    /**
     * @return iterable<string, mixed>
     */
    #[Override]
    public function getMultiple($keys, $default = null): iterable
    {
        throw new InvalidKey('The keys are refused.');
    }

    #[Override]
    public function has($key): bool
    {
        throw new InvalidKey("The key {$key} is refused.");
    }

    #[Override]
    public function set($key, $value, $ttl = null): bool
    {
        throw new InvalidKey("The key {$key} is refused.");
    }

    #[Override]
    public function setMultiple($values, $ttl = null): bool
    {
        throw new InvalidKey('The keys are refused.');
    }
}
