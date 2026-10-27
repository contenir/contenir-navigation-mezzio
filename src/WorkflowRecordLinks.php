<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Workflow\WorkflowResource;
use Contenir\Workflow\Workflow\WorkflowInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Mezzio\Router\Exception\ExceptionInterface as RouterException;
use Mezzio\Router\RouterInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;

use function array_keys;
use function array_unique;
use function array_values;
use function ctype_digit;

/**
 * A Contenir site's record links: each record a menu links to, read in one query from the
 * published resources of the types its section edits, at the address its workflow routes it at.
 *
 * The sections map each CMS section the site lets editors link to, such as `site.page.home`, to
 * the resource type it edits, such as `page`. A record of a section not in the map, of another
 * type than its section edits, not published, or whose workflow registers no route for it, has
 * no link.
 *
 * @api
 */
final readonly class WorkflowRecordLinks implements RecordLinkResolverInterface
{
    /**
     * @param array<string, string> $sections The resource type each linkable CMS section edits.
     */
    public function __construct(
        private ResourceManagerInterface $resources,
        private WorkflowPluginManager $workflows,
        private RouterInterface $router,
        private array $sections,
    ) {}

    /**
     * @throws DbModelException
     * @throws MissingResourceException Never for a resource read from the database, which has its id.
     */
    #[Override]
    public function resolve(array $records): RecordLinks
    {
        $sectionsById = [];
        foreach ($records as $record) {
            if (null === ($this->sections[$record->section] ?? null) || ! ctype_digit($record->id)) {
                continue;
            }

            $sectionsById[$record->id][] = $record->section;
        }

        if ([] === $sectionsById) {
            return RecordLinks::none();
        }

        $links = [];
        foreach ($this->resources->findByType(array_values(array_unique($this->sections)), [
            'resourceId' => array_keys($sectionsById),
            'status'     => ResourceStatus::Active,
        ]) as $resource) {
            $id   = $resource->getId();
            $href = $this->address($resource);
            foreach ($sectionsById[$id] ?? [] as $section) {
                if (null === $href || ($this->sections[$section] ?? null) !== $resource->getType()) {
                    continue;
                }

                $links["{$section}:{$id}"] = new RecordLink($href, (string) $resource->title);
            }
        }

        return RecordLinks::of($links);
    }

    /**
     * The address $resource's workflow routes it at, or none when the site has no such workflow
     * or it registers no route for it.
     */
    private function address(AbstractResourceEntity $resource): ?string
    {
        try {
            /** @var WorkflowInterface $workflow The plugin manager serves workflows only. */
            $workflow = $this->workflows->get($resource->getWorkflowName());
            $workflow->setResource(new WorkflowResource($resource));

            return $this->router->generateUri($workflow->getRouteId());
        } catch (ContainerExceptionInterface|RouterException) {
            return null;
        }
    }
}
