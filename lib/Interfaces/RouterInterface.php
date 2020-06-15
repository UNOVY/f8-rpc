<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc\Interfaces;

use F8\IO\Interfaces\GuardDispatcherInterface;
use F8\Rpc\RpcException;

interface RouterInterface
{
    /**
     * @throws RpcException
     */
    public function add(string $method, GuardDispatcherInterface $taskHandler): void;

    /**
     * @throws RpcException
     */
    public function run(string $method): GuardDispatcherInterface;
}
