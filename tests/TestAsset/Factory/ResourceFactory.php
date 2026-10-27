<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\TestAsset\Factory;

use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;

/**
 * Published resources, as contenir-resource reads them from the database.
 */
final class ResourceFactory
{
    public static function make(int $id, string $type, string $title, ?string $workflow = null): ResourceEntity
    {
        $resource                 = new ResourceEntity();
        $resource->resourceId     = $id;
        $resource->resourceTypeId = $type;
        $resource->title          = $title;
        $resource->workflow       = $workflow;
        $resource->status         = ResourceStatus::Active;

        return $resource;
    }
}
