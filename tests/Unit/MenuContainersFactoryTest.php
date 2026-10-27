<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Mezzio\MenuContainersFactory;
use Contenir\Navigation\Mezzio\MenuOptions;
use Contenir\Navigation\Mezzio\RecordLinkResolverInterface;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Cache\InMemoryCache;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Record\FixedRecordLinks;
use Contenir\Navigation\Mezzio\Tests\Trait\BuildsMenusTrait;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

use function array_keys;

#[Group('unit')]
final class MenuContainersFactoryTest extends TestCase
{
    use BuildsMenusTrait;

    #[Test]
    public function buildsTheMenusFromTheRegisteredServices(): void
    {
        $containers = (new MenuContainersFactory())($this->container());

        static::assertCount(1, $containers->get('footer') ?? []);
    }

    #[Test]
    public function buildsTheMenusWithoutTheApplicationsConfiguration(): void
    {
        $container  = $this->container();
        $containers = (new MenuContainersFactory())(new InMemoryContainer([
            Publisher::class                     => $container->get(Publisher::class),
            NavigationRepositoryInterface::class => $container->get(NavigationRepositoryInterface::class),
            RecordLinkResolverInterface::class   => new FixedRecordLinks(),
        ]));

        static::assertNotNull($containers->get('footer'));
    }

    #[Test]
    public function keepsTheMenusInThePsr16CacheTheConfigurationNames(): void
    {
        $cache = new InMemoryCache();
        (new MenuContainersFactory())($this->container(['cache' => 'menus'], ['menus' => $cache]))->get('footer');

        static::assertSame(['contenir_navigation_footer_5'], array_keys($cache->items));
    }

    #[Test]
    public function refusesACacheServiceThatIsNoCache(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'contenir/contenir-navigation-mezzio: the cache service "menus" is a stdClass, not a'
                . ' Psr\SimpleCache\CacheInterface or a Laminas\Cache\Storage\StorageInterface.',
        );

        (new MenuContainersFactory())($this->container(['cache' => 'menus'], ['menus' => new stdClass()]));
    }

    /**
     * @param array<string, mixed> $options  The package's settings.
     * @param array<string, mixed> $services Services besides the ones the factory reads.
     */
    private function container(
        array $options = [],
        array $services = [],
    ): InMemoryContainer {
        $repository = $this->menus(
            [new Navigation('footer', 'Footer', id: 1, revisionId: 5)],
            [5 => [new Node(Item::url(Url::parse('/privacy'), 'Privacy'))]],
        );

        return new InMemoryContainer([
            'config'                             => [MenuOptions::KEY => $options],
            Publisher::class                     => $this->publisher($repository),
            NavigationRepositoryInterface::class => $repository,
            RecordLinkResolverInterface::class   => new FixedRecordLinks(),
            ...$services,
        ]);
    }
}
