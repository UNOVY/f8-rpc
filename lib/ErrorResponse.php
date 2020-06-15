<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use Throwable;

class ErrorResponse extends AbstractResponse
{
    protected Throwable $error;

    /**
     * @param null|int|string $id
     */
    public function __construct($id, Throwable $error)
    {
        $this->error = $error;

        parent::__construct($id);
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $this->error->getCode(),
                'message' => $this->error->getMessage(),
            ],
            'id' => $this->id,
        ];
    }
}
