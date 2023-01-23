<?php declare(strict_types=1);
namespace Lou117\Core\Container;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    protected array $store = [];


    public function get(string|int $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException();
        }

        return $this->store[$id];
    }


    public function has(string|int $id): bool
    {
        return array_key_exists($id, $this->store);
    }

    /**
     * Stores given `$value` in container, identified by given `$id`.
     *
     * If `$exception_on_duplicate` is set to `true` and given `$id` is already in use, this method will throw an
     * `InvalidArgumentException`. If container is "protected" and given `$id` is one of reserved IDs, this method will
     * throw an `InvalidArgumentException`.
     * @param string $id - contained value unique identifier.
     * @param mixed $value - any value.
     * @param bool $exception_on_duplicate (optional, defaults to `false`) - If set to `true`, this method throws an
     * `InvalidArgumentException` when given `$id` is already used as identifier by another value.
     * @return $this
     * @throws InvalidArgumentException - if `$exception_on_duplicate` parameter is set to `true`, and given `$id` is
     * already used as identifier by another value.
     */
    public function set(string $id, mixed $value, bool $exception_on_duplicate = false): self
    {
        if (
            array_key_exists($id, $this->store)
            && $exception_on_duplicate
        ) {
            throw new InvalidArgumentException("ID {$id} is already in use");
        }

        $this->store[$id] = $value;
        return $this;
    }
}
