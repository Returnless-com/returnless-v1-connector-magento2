<?php

namespace Returnless\Connector\Controller\Order;

use Magento\Framework\App\Action\Context;
use Returnless\Connector\Model\Api\OrderInfo;
use Returnless\Connector\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class Index
 *
 * How to check logs: grep -rn 'returnless' var/log/system.log
 */
class Info extends AbstractController
{
    /**
     * const HEADER_SIGNATURE
     */
    const HEADER_SIGNATURE = 'Returnless-Signature';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderInfo
     */
    protected $orderInfo;

    /**
     * @var array
     */
    protected $response = [];

    /**
     * @var LoggerInterface
     *
     */
    protected $logger;

    /**
     * Info constructor.
     * @param OrderInfo $orderInfo
     * @param Config $config
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        OrderInfo $orderInfo,
        Config $config,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        $this->orderInfo = $orderInfo;

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
       if ($this->checkEnabled()) {
           $incrementId = $this->getRequest()->getParam('increment_id');
           if ($this->checkSignature($incrementId)) {
               // get Order Info
               $response = $this->orderInfo
                   ->setReturnFlag()
                   ->getOrderInfoReturnless($incrementId);

               // set Response
               $this->setResponse("Success!", 200, false, $response);
           }
       }
       $resultJson = $this->resultJsonFactory->create();
       return $resultJson->setData($this->response);
    }
}
