<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Integration;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Mezzio\ConfigProvider;
use Contenir\Navigation\Mezzio\MenuContainers;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Factory\ResourceFactory;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Router\NamedRouteRouter;
use Contenir\Navigation\Mezzio\Tests\Trait\BuildsMenusTrait;
use Contenir\Navigation\Mezzio\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Storage\Adapter\Filesystem;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Navigation\Navigation as Container;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The menus as a site builds them: through the package's ConfigProvider, addressing records
 * through the workflows, kept in a laminas-cache filesystem storage the site's cache clear empties.
 */
#[Group('integration')]
final class MenuContainersTest extends TestCase
{
    use BuildsMenusTrait;
    use TemporaryDirectoryTrait;

    /**
     * Each top-level page's address, by its label.
     *
     * @param Container<AbstractPage>|null $container
     *
     * @return array<string, string>
     */
    private static function hrefs(?Container $container): array
    {
        $hrefs = [];
        foreach ($container ?? new Container() as $page) {
            $hrefs[(string) $page->getLabel()] = $page->getHref();
        }

        return $hrefs;
    }

    #[Test]
    public function drawsAMenuAfreshOnceTheStorageIsCleared(): void
    {
        $this->services(['page-12' => '/about'])->get(MenuContainers::class)->get('footer');
        (new SimpleCacheDecorator($this->storage()))->clear();

        $menus = $this->services(['page-12' => '/moved'])->get(MenuContainers::class);

        static::assertSame(['About' => '/moved#team', 'Press' => '/press'], self::hrefs($menus->get('footer')));
    }

    #[Test]
    public function drawsAMenuFromTheStorageOnceBuilt(): void
    {
        $this->services(['page-12' => '/about'])->get(MenuContainers::class)->get('footer');

        $menus = $this->services(['page-12' => '/moved'])->get(MenuContainers::class);

        static::assertSame(['About' => '/about#team', 'Press' => '/press'], self::hrefs($menus->get('footer')));
    }

    #[Test]
    public function drawsAMenuWithItsRecordsAtTheAddressesTheirWorkflowsRouteThemAt(): void
    {
        $menus = $this->services(['page-12' => '/about'])->get(MenuContainers::class);

        static::assertSame(['About' => '/about#team', 'Press' => '/press'], self::hrefs($menus->get('footer')));
    }

    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    /**
     * The site's services with the package's, a router holding $routes, and the menu storage.
     *
     * @param array<string, string> $routes Each route's address, by its name.
     */
    private function services(array $routes): ServiceManager
    {
        $repository = $this->menus(
            [new Navigation('footer', 'Footer', id: 1, revisionId: 5)],
            [5 => [
                new Node(Item::record(new RecordTarget('site.page.home', 12), fragment: 'team')),
                new Node(Item::url(Url::parse('/press'), 'Press')),
            ]],
        );
        $resources = $this->createStub(ResourceManagerInterface::class);
        $resources->method('findByType')->willReturn([ResourceFactory::make(12, 'page', 'About')]);
        $config = (new ConfigProvider())();

        return new ServiceManager([
            ...$config['dependencies'],
            'services' => [
                'config'                             => [
                    ...$config,
                    'contenir_navigation_mezzio' => [
                        'cache'    => 'menus',
                        'sections' => ['site.page.home' => 'page'],
                    ],
                ],
                'menus'                              => $this->storage(),
                Publisher::class                     => $this->publisher($repository),
                NavigationRepositoryInterface::class => $repository,
                ResourceManagerInterface::class      => $resources,
                WorkflowPluginManager::class         => new WorkflowPluginManager(new ServiceManager()),
                RouterInterface::class               => new NamedRouteRouter($routes),
            ],
        ]);
    }

    private function storage(): Filesystem
    {
        $storage = new Filesystem(['cache_dir' => $this->temporaryDirectory, 'namespace' => 'menus']);
        $storage->addPlugin(new Serializer());

        return $storage;
    }
}
