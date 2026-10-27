<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Mezzio\MenuOptions;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Factory\ResourceFactory;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Router\NamedRouteRouter;
use Contenir\Navigation\Mezzio\WorkflowRecordLinksFactory;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class WorkflowRecordLinksFactoryTest extends TestCase
{
    #[Test]
    public function linksNoSectionsWithoutTheApplicationsConfiguration(): void
    {
        $record = new RecordTarget('site.page.home', 12);
        $links  = (new WorkflowRecordLinksFactory())($this->container());

        static::assertNull($links->resolve([$record])->for($record));
    }

    #[Test]
    public function linksTheSectionsTheConfigurationMaps(): void
    {
        $record = new RecordTarget('site.page.home', 12);
        $links  = (new WorkflowRecordLinksFactory())($this->container([
            'config' => [MenuOptions::KEY => ['sections' => ['site.page.home' => 'page']]],
        ]));

        static::assertSame('/about', $links->resolve([$record])->for($record)?->href);
    }

    /**
     * @param array<string, mixed> $services
     */
    private function container(array $services = []): InMemoryContainer
    {
        $resources = $this->createStub(ResourceManagerInterface::class);
        $resources->method('findByType')->willReturn([ResourceFactory::make(12, 'page', 'About')]);

        return new InMemoryContainer([
            ResourceManagerInterface::class => $resources,
            WorkflowPluginManager::class    => new WorkflowPluginManager(new InMemoryContainer()),
            RouterInterface::class          => new NamedRouteRouter(['page-12' => '/about']),
            ...$services,
        ]);
    }
}
