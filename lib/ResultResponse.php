<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use JsonSerializable;

class ResultResponse extends AbstractResponse
{
    protected JsonSerializable $result;

    /**
     * @param null|int|string $id
     */
    public function __construct($id, JsonSerializable $result)
    {
        $this->result = $result;

        parent::__construct($id);
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => '2.0',
            'result' => $this->result->jsonSerialize(),
            'id' => $this->id,
        ];
    }
}
