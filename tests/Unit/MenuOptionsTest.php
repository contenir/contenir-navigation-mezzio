<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Unit;

use Contenir\Navigation\Mezzio\MenuOptions;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MenuOptionsTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidConfigProvider(): array
    {
        $sections = 'contenir_navigation_mezzio.sections must be a map of CMS sections to resource types';

        return [
            'options not an array'  => [
                ['options' => 'menus'],
                'contenir_navigation_mezzio must be an array, not string',
            ],
            'cache not a string'    => [
                ['cache' => 5],
                'contenir_navigation_mezzio.cache must be a service name, not int',
            ],
            'cache empty'           => [
                ['cache' => ''],
                'contenir_navigation_mezzio.cache must be a service name, not string',
            ],
            'sections not an array' => [['sections' => 'page'], "{$sections}, not string"],
            'section not named'     => [['sections' => ['page']], "{$sections}, not array"],
            'type not a string'     => [['sections' => ['site.page.home' => 5]], "{$sections}, not array"],
            'type empty'            => [['sections' => ['site.page.home' => '']], "{$sections}, not array"],
        ];
    }

    #[Test]
    public function hasNoCacheAndNoSectionsWhenNothingIsSet(): void
    {
        $options = MenuOptions::fromAppConfig([]);

        static::assertSame([null, []], [$options->cache, $options->sections]);
    }

    #[Test]
    public function readsTheCacheServiceAndTheSections(): void
    {
        $options = MenuOptions::fromAppConfig([
            MenuOptions::KEY => [
                'cache'    => 'menus',
                'sections' => ['site.page.home' => 'page', 'library.projects.article' => 'project'],
            ],
        ]);

        static::assertSame(
            ['menus', ['site.page.home' => 'page', 'library.projects.article' => 'project']],
            [$options->cache, $options->sections],
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[Test]
    #[DataProvider('invalidConfigProvider')]
    public function refusesASettingOfTheWrongKind(array $config, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        MenuOptions::fromAppConfig([MenuOptions::KEY => $config['options'] ?? $config]);
    }
}
