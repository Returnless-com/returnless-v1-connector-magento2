<?php

namespace Returnless\Connector\Model\Api;

use Returnless\Connector\Api\GiftCardAccountInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftCardModel;
use Magento\GiftCardAccount\Model\Pool;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Interface OrderCoupon
 */
class GiftCardAccount implements GiftCardAccountInterface
{
    /**
     * const NAMESPACE_MODULE
     */
    const NAMESPACE_MODULE = 'Returnless_Connector';

    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Order
     */
    private $orderModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceInterface $moduleResource
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param Order $orderModel
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceInterface $moduleResource,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        Order $orderModel,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleResource = $moduleResource;
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->orderModel = $orderModel;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $requestParams
     * @return array
     */
    public function createGiftCardAccount($requestParams)
    {
        $response['installed_module_version'] = $this->moduleResource->getDbVersion(self::NAMESPACE_MODULE);
        $codePool = $this->objectManager->create(Pool::class);
        $codes = $codePool->getCollection()->addFieldToFilter('status', Pool::STATUS_FREE)->getSize();
        if (!$codes) {
            $codePool->generatePool();
        }
        $model = $this->objectManager->create(GiftCardModel::class);
        $order = $this->orderModel->loadByIncrementId($requestParams['order_id']);
        if ($order->getId()) {
            try {
                $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
            } catch (\Exception $e) {
                $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
                $this->logger->error($e->getMessage());
            }
        } else {
            $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
        }
        $data = [
            'status' => 1,
            'is_redeemable' => 1,
            'website_id' => $websiteId,
            'balance' => $requestParams['coupon_amount'],
        ];
        $model->addData($data);

        try {
            $model->save();
            if ($model->getId()) {
                $response['result']['coupon_id'] = $model->getId();
                $response['result']['coupon_code'] = $model->getCode();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $response;
    }
}
