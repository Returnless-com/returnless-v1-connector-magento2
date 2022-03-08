<?php

namespace Returnless\Connector\Api;

/**
 * Interface GiftCardAccountInterface
 */
interface GiftCardAccountInterface
{
    /**
     * Creates Coupon By the Request Params
     *
     * @api
     * @param $requestParams
     * @return mixed
     */
    public function createGiftCardAccount($requestParams);
}
