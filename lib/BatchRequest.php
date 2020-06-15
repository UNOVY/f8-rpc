<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

class BatchRequest
{
    /**
     * @var array<Notification|Request|RpcException>
     */
    protected array $calls;

    /**
     * @param array<Notification|Request|RpcException> $calls
     */
    public function __construct(array $calls)
    {
        $this->calls = $calls;
    }

    /**
     * @return array<Notification|Request|RpcException>
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    public function jsonSerialize(): array
    {
        return $this->calls;
    }
}
