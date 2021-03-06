<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 15:36
 */
namespace Lou117\Core\Container;

use \InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * Container internal array.
     * @var array
     */
    protected $store = [];


    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->has($id) === false) {
            throw new NotFoundException();
        }

        return $this->store[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return array_key_exists($id, $this->store);
    }

    /**
     * Stores given $value in container, identified by given $id. If $exception_on_duplicate is set to TRUE and given
     * $id is already in use, this method will throw an InvalidArgumentException. If container is "protected" and given
     * $id is one of reserved IDs, this method will throw an InvalidArgumentException.
     * @param string $id - Contained value unique identifier.
     * @param mixed $value - Any value.
     * @param bool $exception_on_duplicate (optional, defaults to FALSE) - If set to TRUE, this method throws an
     * InvalidArgumentException when given $id is already used as identifier by another value.
     * @return Container
     * @throws InvalidArgumentException - if $exception_on_duplicate parameter is set to TRUE, and given $id is already
     * used as identifier by another value.
     */
    public function set(string $id, $value, $exception_on_duplicate = false): self
    {
        if (array_key_exists($id, $this->store) && $exception_on_duplicate) {
            throw new InvalidArgumentException("ID {$id} is already in use");
        }

        $this->store[$id] = $value;
        return $this;
    }
}
