<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

class Request extends Notification
{
    /**
     * @var null|int|string
     */
    protected $id;

    /**
     * @param null|int|string                  $id
     * @param array<mixed>|array<string,mixed> $params
     */
    public function __construct($id, string $method, array $params)
    {
        $this->id = $id;

        parent::__construct($method, $params);
    }

    /**
     * @return null|int|string
     */
    public function getId()
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => '2.0',
            'method' => $this->getMethod(),
            'params' => $this->getParams(),
            'id' => $this->getId(),
        ];
    }
}
