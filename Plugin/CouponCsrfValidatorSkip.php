<?php

namespace Returnless\Connector\Plugin;

/**
 * Class CouponCsrfValidatorSkip for M2 v2.3
 */
class CouponCsrfValidatorSkip
{
    /**
     * Method skip
     *
     * @param $subject
     * @param \Closure $proceeds
     * @param $request
     * @param $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {

        if ($request->getModuleName() == 'returnless_connector') {
            return;
        }

        $proceed($request, $action);
    }
}
