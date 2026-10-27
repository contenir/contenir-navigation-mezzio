<?php

declare(strict_types=1);

namespace Contenir\Navigation\Mezzio\Tests\TestAsset\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as InvalidCacheArgument;

final class InvalidKey extends InvalidArgumentException implements InvalidCacheArgument {}
