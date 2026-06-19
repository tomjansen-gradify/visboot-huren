<?php

declare(strict_types=1);

namespace Gradify\Traits;

use Gradify\Exceptions\MissingBootMethodException;

trait Register
{
    public static function register(): self
    {
        $instance = new static();
        $instance->boot();

        return $instance;
    }

    public function boot(): void
    {
        throw new MissingBootMethodException(sprintf('Missing boot method for class %s', static::class));
    }
}
