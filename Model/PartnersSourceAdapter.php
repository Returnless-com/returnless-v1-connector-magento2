<?php

namespace Returnless\Connector\Model;

use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;;
use Returnless\Connector\Model\Config;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class PartnersSourceAdapter
 * @package Returnless\Connector\Model
 */
class PartnersSourceAdapter
{
    /**
     * @var array|mixed
     */
    private $partnersResource;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     *
     */
    private $logger;

    /**
     * PartnersSourceAdapter constructor.
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Returnless\Connector\Model\Config $config
     * @param array $partnersResource
     */
    public function __construct(
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Config $config,
        Logger $logger,
        $partnersResource = []
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->partnersResource = $partnersResource;
    }

    /**
     * @deprecated
     *
     * @param $orderId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getOrderById($orderId)
    {
        $order = $this->getOrderByMagento($orderId);
        if (!$order->getId() && $this->config->getMarketplaceSearchEnabled()) {
            try {
                $order = $this->getOrderByMarketplace($orderId);
            } catch (\Throwable $e) {
                $this->logger->critical('Returnless Error PartnersSourceAdapter',  ['exception' => $e]);
            }
        }

        return $order;
    }

    /**
     * @param $orderId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getOrderByMarketplace($orderId)
    {
        $partnerId = $this->config->getMarketplaceSearchPartnerId();
        $collection = $this->getPartnersCollectionModel($partnerId);
        $partnerOrder = $collection->addFieldToFilter(
            $this->partnersResource[$partnerId]['keyForSearch'],
            ['eq' => $orderId]
        )
            ->addFieldToSelect('order_id')
            ->getFirstItem();
        $order =  $this->getOrderByMagento($partnerOrder->getOrderId(), 'increment_id');

        return $order;
    }

    /**
     * @param $incrementId
     * @param string $searchKey
     * @return \Magento\Framework\DataObject
     */
    private function getOrderByMagento($incrementId, $searchKey = 'increment_id')
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                $searchKey,
                $incrementId,
                'eq'
            )
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getFirstItem();
    }

    /**
     * @param $partnerId
     * @return false|mixed
     */
    private function getPartnersCollectionModel($partnerId)
    {
        $result = false;
        if (!empty($this->partnersResource[$partnerId])) {
            $result = $this->partnersResource[$partnerId]['model'];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getIntegrationPartners()
    {
        $result = [];
        if(!empty($this->partnersResource)) {
            foreach ($this->partnersResource as $partnerIntegrationCode => $value) {
                $result[] = [
                    'value' => $partnerIntegrationCode,
                    'label' => __($value['label'])
                ];
            }
        }

        return $result;
    }
}
