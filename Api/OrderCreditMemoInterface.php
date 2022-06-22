<?php

namespace Returnless\Connector\Api;

/**
 * Interface OrderCreditMemoInterface
 */
interface OrderCreditMemoInterface
{
    /**
     * Creates Credit Memo By the Request Params
     *
     * @api
     * @param $requestParams
     * @return mixed
     */
    public function createCreditMemo($requestParams);
}
