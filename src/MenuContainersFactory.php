<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use InvalidArgumentException;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

use function assert;
use function get_debug_type;
use function is_array;
use function sprintf;

/**
 * Builds the menu containers from contenir-navigation's publisher and repository, the record
 * resolver registered under RecordLinkResolverInterface, and the cache MenuOptions names: a
 * PSR-16 cache as it is, a laminas-cache storage wrapped as one.
 *
 * @api
 */
final readonly class MenuContainersFactory
{
    /**
     * @throws RuntimeException
     */
    private static function cache(mixed $service, string $name): CacheInterface
    {
        return match (true) {
            $service instanceof CacheInterface => $service,
            $service instanceof StorageInterface => new SimpleCacheDecorator($service),
            default => throw new RuntimeException(sprintf(
                'contenir/contenir-navigation-mezzio: the cache service "%s" is a %s,'
                    . ' not a Psr\SimpleCache\CacheInterface or a Laminas\Cache\Storage\StorageInterface.',
                $name,
                get_debug_type($service),
            )),
        };
    }

    /**
     * @throws ContainerExceptionInterface When a service cannot be built.
     * @throws InvalidArgumentException    When a setting is not of the kind it needs to be.
     * @throws RuntimeException            When the cache is neither PSR-16 nor a laminas-cache storage.
     */
    public function __invoke(ContainerInterface $container): MenuContainers
    {
        /** @var mixed $config */
        $config  = $container->has('config') ? $container->get('config') : null;
        $options = MenuOptions::fromAppConfig(is_array($config) ? $config : []);

        $publisher = $container->get(Publisher::class);
        assert($publisher instanceof Publisher, description: 'contenir-navigation registers its publisher');

        $menus = $container->get(NavigationRepositoryInterface::class);
        assert(
            $menus instanceof NavigationRepositoryInterface,
            description: 'contenir-navigation registers its repository under its interface',
        );

        $records = $container->get(RecordLinkResolverInterface::class);
        assert(
            $records instanceof RecordLinkResolverInterface,
            description: 'The record resolver is registered under its interface',
        );

        return new MenuContainers(
            $publisher,
            $menus,
            $records,
            null === $options->cache ? null : self::cache($container->get($options->cache), $options->cache),
        );
    }
}
