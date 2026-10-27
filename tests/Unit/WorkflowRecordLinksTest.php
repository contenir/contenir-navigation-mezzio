<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Mezzio\RecordLink;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Factory\ResourceFactory;
use Contenir\Navigation\Mezzio\Tests\TestAsset\Router\NamedRouteRouter;
use Contenir\Navigation\Mezzio\WorkflowRecordLinks;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class WorkflowRecordLinksTest extends TestCase
{
    private const string PAGES = 'site.page.home';

    private const string PROJECTS = 'library.projects.article';

    private const array SECTIONS = [self::PAGES => 'page', self::PROJECTS => 'project', 'site.page.legal' => 'page'];

    #[Test]
    public function hasNoLinkForARecordItsWorkflowGivesNoRoute(): void
    {
        $links = $this->links([ResourceFactory::make(12, 'page', 'About')], []);

        static::assertNull($links->resolve([new RecordTarget(self::PAGES, 12)])->for(new RecordTarget(
            self::PAGES,
            12,
        )));
    }

    #[Test]
    public function hasNoLinkForARecordOfAnotherTypeThanItsSectionEdits(): void
    {
        $links = $this->links([ResourceFactory::make(12, 'page', 'About')], ['page-12' => '/about']);

        static::assertNull($links->resolve([new RecordTarget(self::PROJECTS, 12)])->for(new RecordTarget(
            self::PROJECTS,
            12,
        )));
    }

    #[Test]
    public function hasNoLinkForARecordOfAWorkflowTheSiteDoesNotHave(): void
    {
        $links = $this->links([ResourceFactory::make(12, 'page', 'About', 'missing')], ['page-12' => '/about']);

        static::assertNull($links->resolve([new RecordTarget(self::PAGES, 12)])->for(new RecordTarget(
            self::PAGES,
            12,
        )));
    }

    #[Test]
    public function linksARecordFromEachSectionThatEditsItsType(): void
    {
        $links = $this->links([ResourceFactory::make(12, 'page', 'About')], ['page-12' => '/about'])->resolve([
            new RecordTarget(self::PROJECTS, 12),
            new RecordTarget(self::PAGES, 12),
            new RecordTarget('site.page.legal', 12),
        ]);

        static::assertSame(
            [null, '/about', '/about'],
            [
                $links->for(new RecordTarget(self::PROJECTS, 12))?->href,
                $links->for(new RecordTarget(self::PAGES, 12))?->href,
                $links->for(new RecordTarget('site.page.legal', 12))?->href,
            ],
        );
    }

    #[Test]
    public function linksARecordToTheAddressItsWorkflowRoutesItAtTitledByItsTitle(): void
    {
        $links = $this->links([ResourceFactory::make(12, 'page', 'About')], ['page-12' => '/about']);

        static::assertEquals(
            new RecordLink('/about', 'About'),
            $links->resolve([new RecordTarget(self::PAGES, 12)])->for(new RecordTarget(self::PAGES, 12)),
        );
    }

    #[Test]
    public function readsNothingForRecordsOfSectionsItDoesNotLink(): void
    {
        $resources = $this->createMock(ResourceManagerInterface::class);
        $resources->expects($this->never())->method('findByType');

        (new WorkflowRecordLinks($resources, $this->workflows(), new NamedRouteRouter([]), self::SECTIONS))
            ->resolve([new RecordTarget('site.news.article', 12), new RecordTarget(self::PAGES, 'home')]);
    }

    #[Test]
    public function readsThePublishedRecordsOfTheLinkableTypesInOneQuery(): void
    {
        $resources = $this->createMock(ResourceManagerInterface::class);
        $resources->expects($this->once())
            ->method('findByType')
            ->with(['page', 'project'], ['resourceId' => [12, 31], 'status' => ResourceStatus::Active])
            ->willReturn([]);

        (new WorkflowRecordLinks($resources, $this->workflows(), new NamedRouteRouter([]), self::SECTIONS))
            ->resolve([
                new RecordTarget('site.news.article', 40),
                new RecordTarget(self::PAGES, 12),
                new RecordTarget(self::PROJECTS, 31),
                new RecordTarget('site.page.legal', 12),
            ]);
    }

    /**
     * @param list<ResourceEntity>  $published
     * @param array<string, string> $routes    Each route's address, by its name.
     */
    private function links(array $published, array $routes): WorkflowRecordLinks
    {
        $resources = $this->createStub(ResourceManagerInterface::class);
        $resources->method('findByType')->willReturn($published);

        return new WorkflowRecordLinks($resources, $this->workflows(), new NamedRouteRouter($routes), self::SECTIONS);
    }

    private function workflows(): WorkflowPluginManager
    {
        return new WorkflowPluginManager(new InMemoryContainer());
    }
}
