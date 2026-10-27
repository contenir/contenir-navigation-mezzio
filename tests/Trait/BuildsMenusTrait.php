<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\Trait;

use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\Revision;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Navigation\Storage\TransactionInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Menus held by a stubbed repository, as contenir-navigation's repository reads them: the first
 * published menu with a handle is the one shown, at the revision with its id.
 */
trait BuildsMenusTrait
{
    /**
     * @param list<Navigation> $menus
     */
    private static function shown(array $menus, string $handle): ?Navigation
    {
        $first = null;
        foreach ($menus as $menu) {
            if ($handle !== $menu->handle) {
                continue;
            }

            if ($menu->isLive()) {
                return $menu;
            }

            $first ??= $menu;
        }

        return $first;
    }

    /**
     * @param list<Navigation>       $menus
     * @param array<int, list<Node>> $revisions Each revision's nodes, by its id.
     */
    private function menus(array $menus, array $revisions = []): NavigationRepositoryInterface
    {
        $repository = $this->createStub(NavigationRepositoryInterface::class);
        $repository->method('navigations')->willReturn($menus);
        $repository->method('navigationByHandle')
            ->willReturnCallback(static fn(string $handle): ?Navigation => self::shown($menus, $handle));
        $repository->method('liveRevisionId')
            ->willReturnCallback(static fn(string $handle): ?int => self::shown($menus, $handle)?->revisionId);
        $repository->method('revision')
            ->willReturnCallback(static fn(int $id): Revision => new Revision(
                1,
                $revisions[$id] ?? throw NotFoundException::revision($id),
                new DateTimeImmutable('2026-10-27'),
                id: $id,
            ));

        return $repository;
    }

    private function publisher(NavigationRepositoryInterface $repository): Publisher
    {
        return new Publisher(
            $repository,
            $this->createStub(TransactionInterface::class),
            $this->createStub(ClockInterface::class),
        );
    }
}
