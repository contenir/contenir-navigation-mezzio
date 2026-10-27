<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Mezzio\ConfigProvider;
use Contenir\Navigation\Mezzio\MenuContainers;
use Contenir\Navigation\Mezzio\MenuContainersFactory;
use Contenir\Navigation\Mezzio\RecordLinkResolverInterface;
use Contenir\Navigation\Mezzio\View\Menus;
use Contenir\Navigation\Mezzio\View\MenusFactory;
use Contenir\Navigation\Mezzio\WorkflowRecordLinks;
use Contenir\Navigation\Mezzio\WorkflowRecordLinksFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function givesItsDependenciesAndViewHelpersOnTheirOwn(): void
    {
        $provider = new ConfigProvider();
        $config   = $provider();

        static::assertSame(
            [$config['dependencies'], $config['view_helpers']],
            [$provider->getDependencies(), $provider->getViewHelpers()],
        );
    }

    #[Test]
    public function registersTheServicesTheViewHelperAndTheDefaultSettings(): void
    {
        static::assertSame(
            [
                'dependencies'               => [
                    'aliases'   => [RecordLinkResolverInterface::class => WorkflowRecordLinks::class],
                    'factories' => [
                        MenuContainers::class      => MenuContainersFactory::class,
                        WorkflowRecordLinks::class => WorkflowRecordLinksFactory::class,
                    ],
                ],
                'view_helpers'               => [
                    'aliases'   => ['menus' => Menus::class],
                    'factories' => [Menus::class => MenusFactory::class],
                ],
                'contenir_navigation_mezzio' => ['cache' => null, 'sections' => []],
            ],
            (new ConfigProvider())(),
        );
    }
}
