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
     * Is container protected from reserved ids override ?
     * @var bool
     */
    protected $isProtected = false;

    /**
     * When container is "protected", use of reserved ids is prohibited.
     * @var array
     */
    protected $reservedIds = [ "logger", "request", "route", "settings"];

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
     * Is container "protected"?
     * @return bool
     */
    public function isProtected(): bool
    {
        return $this->isProtected;
    }

    /**
     * "Protects" container from reserved ids override. Called by Core class.
     * @return Container
     */
    public function protect(): self
    {
        $this->isProtected = true;
        return $this;
    }

    /**
     * Stores given $value in container, identified by given $id. If $exception_on_duplicate is set to TRUE and given
     * $id is already in use, this method will throw an InvalidArgumentException. If container is "protected" and given
     * $id is one of reserved IDs, this method will throw an InvalidArgumentException.
     * @param string $id
     * @param $value
     * @param bool $exception_on_duplicate
     * @return Container
     */
    public function set(string $id, $value, $exception_on_duplicate = false): self
    {
        if ($this->isProtected && in_array($id, $this->reservedIds)) {

            throw new InvalidArgumentException("ID {$id} is reserved");

        }

        if (array_key_exists($id, $this->store) && $exception_on_duplicate) {

            throw new InvalidArgumentException("ID {$id} is already in use");

        }

        $this->store[$id] = $value;
        return $this;
    }
}
