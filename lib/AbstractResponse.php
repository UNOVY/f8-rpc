<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use JsonSerializable;

abstract class AbstractResponse implements JsonSerializable
{
    /**
     * @var null|int|string
     */
    protected $id;

    /**
     * @param null|int|string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return null|int|string
     */
    public function getId()
    {
        return $this->id;
    }

    abstract public function toArray(): array;

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
