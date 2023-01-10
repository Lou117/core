<?php declare(strict_types=1);
namespace Lou117\Core\Routing;

class Route
{
    public ?array $arguments = null;

    public ?array $attributes = null;

    public ?string $controller = null;

    public ?string $endpoint = null;

    public ?array $methods = null;

    public ?string $name = null;
}
