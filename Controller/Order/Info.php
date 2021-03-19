<?php

namespace Returnless\Connector\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Returnless\Connector\Model\Api\OrderInfo;
use Returnless\Connector\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Class Index
 *
 * How to check logs: grep -rn 'returnless' var/log/system.log
 */
class Info extends Action
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
     *
     * @param OrderInfo $orderInfo
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Context $context
     */
    public function __construct(
        OrderInfo $orderInfo,
        Config $config,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->orderInfo = $orderInfo;
        $this->config = $config;
        $this->logger = $logger;

        return parent::__construct($context);
    }

    /**
     * Execution method
     *
     * @return void
     */
    public function execute()
    {
        $response = [];

        // validate if Service is enabled
        $enabled = $this->config->getEnabled();
        if (empty($enabled)) {
            $this->setResponse("Service is disabled!", 423, true)
                ->returnResponse();
        }

        // get Signature from Header
        $returnlessSignature = $this->getSignatureFromHeader();

        // get Signature from Url
        if (empty($returnlessSignature)) {
            $returnlessSignature = $this->getSignatureFromUrl();
        }

        // 1 validate Signature
        if (empty($returnlessSignature)) {
            $this->setResponse("Can't find header: '" . self::HEADER_SIGNATURE . "'", 401, true)
                ->returnResponse();
        }

        // 2 validate Signature
        $incrementId = $this->getRequest()->getParam('increment_id');
        $integrationApiPassword = $this->config->getApiPassword();
        $hashedSignature = hash_hmac("sha256" , $incrementId , $integrationApiPassword);
        if ($returnlessSignature != $hashedSignature) {
            $this->setResponse("Signature is not valid!", 403, true)
                ->returnResponse();
        }

        // get Order Info
        $response = $this->orderInfo
            ->setReturnFlag()
            ->getOrderInfoReturnless($incrementId);

        // set Response and Return
        $this->setResponse("Success!", 200)
            ->returnResponse($response);
    }

    /**
     * Set Response message
     *
     * @param string $message
     * @param int $code
     * @param bool $debug
     * @return $this
     */
    protected function setResponse($message = '', $code = 0, $debug = false)
    {
        if ($debug) {
            $this->logger->notice("[RET_ORDER_INFO] " . $message);
        }

        $this->response['return_code'] = $code;
        $this->response['return_message'] = $message;

        return $this;
    }

    /**
     * Get Signature form Header
     *
     * @return bool|mixed
     */
    protected function getSignatureFromHeader()
    {
        $returnlessSignature = false;

        if (!empty($this->getRequest()->getHeader(self::HEADER_SIGNATURE))) {
            $returnlessSignature = $this->getRequest()->getHeader(self::HEADER_SIGNATURE);
        }

        return $returnlessSignature;
    }

    /**
     * Get Signature form Url
     *
     * @return bool|mixed
     */
    protected function getSignatureFromUrl()
    {
        $returnlessSignature = false;

        if (!empty($this->getRequest()->getParam(self::HEADER_SIGNATURE))) {
            $returnlessSignature = $this->getRequest()->getParam(self::HEADER_SIGNATURE);
        }

        return $returnlessSignature;
    }

    /**
     * Apply Response Array
     *
     * @param $result
     */
    protected function returnResponse($result = null)
    {
        header("Content-Type: application/json; charset=utf-8");

        if (isset($result['installed_module_version']) && !empty($result['installed_module_version'])) {
            $this->response['installed_module_version'] = $result['installed_module_version'];
        }

        if (isset($result['result']) && !empty($result['result'])) {
            $this->response['result'] = $result['result'];
        }

        if (isset($result['return_message']) && !empty($result['return_message'])) {
            $this->response['return_message'] = $result['return_message'];
        }

        $response = json_encode($this->response);
        print_r($response,false);

        die();
    }
}
