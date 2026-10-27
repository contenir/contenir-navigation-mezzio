<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use InvalidArgumentException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;

/**
 * Builds the workflow record links from contenir-resource's manager, the site's workflows, the
 * router, and the sections MenuOptions maps.
 *
 * @api
 */
final readonly class WorkflowRecordLinksFactory
{
    /**
     * @throws ContainerExceptionInterface When a service cannot be built.
     * @throws InvalidArgumentException    When a setting is not of the kind it needs to be.
     */
    public function __invoke(ContainerInterface $container): WorkflowRecordLinks
    {
        /** @var mixed $config */
        $config  = $container->has('config') ? $container->get('config') : null;
        $options = MenuOptions::fromAppConfig(is_array($config) ? $config : []);

        $resources = $container->get(ResourceManagerInterface::class);
        assert($resources instanceof ResourceManagerInterface, description: 'contenir-resource registers its manager');

        $workflows = $container->get(WorkflowPluginManager::class);
        assert(
            $workflows instanceof WorkflowPluginManager,
            description: 'The site registers its workflows with contenir-workflow-mezzio',
        );

        $router = $container->get(RouterInterface::class);
        assert($router instanceof RouterInterface, description: 'Mezzio registers its router');

        return new WorkflowRecordLinks($resources, $workflows, $router, $options->sections);
    }
}
