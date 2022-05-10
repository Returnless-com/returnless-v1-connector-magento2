<?php

namespace Returnless\Connector\Controller\Order;

use Magento\Framework\App\Action\Context;
use Returnless\Connector\Model\Api\OrderCreditMemo;
use Returnless\Connector\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Class Refund
 */
class Refund extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var OrderCreditMemo
     */
    protected $orderCreditMemo;

    /**
     * CreditMemo constructor.
     *
     * @param OrderCreditMemo $orderCreditMemo
     * @param Config $config
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        OrderCreditMemo $orderCreditMemo,
        Config $config,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        $this->orderCreditMemo = $orderCreditMemo;
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
        if ($this->checkEnabled()
            && $this->checkSignature($requestData['return_id'])
        ) {
            $response = $this->orderCreditMemo->createCreditMemo($requestData);
            // set Response
            $this->setResponse($response['return_message'], $response['code'], false, $response);
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($this->response);
    }
}
