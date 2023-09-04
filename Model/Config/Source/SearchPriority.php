<?php

namespace Returnless\Connector\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class IntegrationPartner
 * @package Returnless\Connector\Model\Config
 */
class SearchPriority implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return
            [
                [
                    'value' => 'magento',
                    'label' => __('Magento orders, Marketplace orders')
                ],
                [
                    'value' => 'marketplace',
                    'label' => __('Marketplace orders, Magento orders')
                ]
            ];
    }
}
