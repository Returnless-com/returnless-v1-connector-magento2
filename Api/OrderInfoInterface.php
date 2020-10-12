<?php

namespace Returnless\Connector\Api;

/**
 * Interface OrderInfoInterface
 */
interface OrderInfoInterface
{
    /**
     * Returns Order Info By the Increment Id
     *
     * @api
     * @param string $incrementId
     * @return mixed
     */
    public function getOrderInfoReturnless($incrementId);
}
