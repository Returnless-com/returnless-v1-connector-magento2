<?php

namespace Returnless\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class GenerationType
 * @package Returnless\Connector\Model\Config\Source
 */
class GenerationType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return
            [
                [
                    'value' => 'coupon',
                    'label' => __('Coupon code')
                ],
                [
                    'value' => 'gift',
                    'label' => __('Gift card')
                ]
            ];
    }
}
