<?php

namespace Returnless\Connector\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class IntegrationPartner
 * @package Returnless\Connector\Model\Config
 */
class IntegrationPartner implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return
            [
                [
                    'value' => 'vendiro',
                    'label' => __('Vendiro')
                ]
            ];
    }
}
