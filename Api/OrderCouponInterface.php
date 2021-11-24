<?php

namespace Returnless\Connector\Api;

/**
 * Interface OrderCouponInterface
 */
interface OrderCouponInterface
{
    /**
     * Creates Coupon By the Request Params
     *
     * @api
     * @param $requestParams
     * @return mixed
     */
    public function createCouponReturnless($requestParams);
}
