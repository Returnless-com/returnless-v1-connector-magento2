<?php

namespace Returnless\Connector\Controller\Order;

use Magento\Framework\App\Action\Context;
use Returnless\Connector\Api\OrderCouponInterface;
use Returnless\Connector\Api\GiftCardAccountInterface;
use Returnless\Connector\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Class Coupon
 */
class Coupon extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var OrderCouponInterface
     */
    protected $orderCoupon;

    /**
     * @var GiftCardAccountInterface
     */
    private $giftCardAccount;

    /**
     * @param OrderCouponInterface $orderCoupon
     * @param GiftCardAccountInterface $giftCardAccount
     * @param Config $config
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        OrderCouponInterface $orderCoupon,
        GiftCardAccountInterface $giftCardAccount,
        Config $config,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        $this->orderCoupon = $orderCoupon;
        $this->giftCardAccount = $giftCardAccount;
        return parent::__construct(
            $config,
            $logger,
            $resultJsonFactory,
            $context
        );
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $requestData = json_decode($this->getRequest()->getContent(), true);

        // validate if Service is enabled
        if ($this->checkEnabled()) {
            if ($this->checkSignature($requestData['return_id'])) {
                if ($this->config->getGenerationType() == 'coupon') {
                    $response = $this->orderCoupon->createCouponReturnless($requestData);
                } else {
                    $response = $this->giftCardAccount->createGiftCardAccount($requestData);
                }
                // set Response
                $this->setResponse("Success!", 200, false, $response);
            }
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($this->response);
    }
}
