<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio;

/**
 * Registers the menu containers, the workflow record links as the record resolver, and the
 * `menus` view helper.
 *
 * @api
 */
final readonly class ConfigProvider
{
    /**
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                RecordLinkResolverInterface::class => WorkflowRecordLinks::class,
            ],
            'factories' => [
                MenuContainers::class      => MenuContainersFactory::class,
                WorkflowRecordLinks::class => WorkflowRecordLinksFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getViewHelpers(): array
    {
        return [
            'aliases'   => [
                'menus' => View\Menus::class,
            ],
            'factories' => [
                View\Menus::class => View\MenusFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies'   => $this->getDependencies(),
            'view_helpers'   => $this->getViewHelpers(),
            MenuOptions::KEY => [
                'cache'    => null,
                'sections' => [],
            ],
        ];
    }
}
