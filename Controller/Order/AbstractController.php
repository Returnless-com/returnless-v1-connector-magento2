<?php

namespace Returnless\Connector\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Returnless\Connector\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class Coupon
 */
abstract class AbstractController extends Action
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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

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
     * AbstractController constructor.
     * @param Config $config
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;

        return parent::__construct($context);
    }

    /**
     * @param string $message
     * @param int $code
     * @param bool $debug
     * @param null $result
     * @return $this
     */
    protected function setResponse($message = '', $code = 0, $debug = false, $result = null)
    {
        if ($debug) {
            $this->logger->notice("[RETURNLESS_CONNECTOR_DEBUG] " . $message);
        }

        header("Content-Type: application/json; charset=utf-8");
        $this->response['return_code'] = $code;
        $this->response['return_message'] = $message;

        if (isset($result['installed_module_version']) && !empty($result['installed_module_version'])) {
            $this->response['installed_module_version'] = $result['installed_module_version'];
        }

        if (isset($result['result']) && !empty($result['result'])) {
            $this->response['result'] = $result['result'];
        }
        return $this;
    }

    /**
     * @return bool
     */
    protected function checkEnabled()
    {
        $enabled = $this->config->getEnabled();
        if (empty($enabled)) {
            $this->setResponse("Service is disabled!", 423, true);
        }
        return !empty($enabled);
    }

    /**
     * @param $incrementId
     * @return bool
     */
    protected function checkSignature($incrementId)
    {
        $isValid = true;

        $hashSignature = $this->getRequest()->getHeader(self::HEADER_SIGNATURE);
        $urlSignature = $this->getRequest()->getParam(self::HEADER_SIGNATURE);
        $returnlessSignature = $hashSignature ? $hashSignature : $urlSignature;

        if (!$incrementId) {
            $this->setResponse("Please enter first key for hash!", 401, true);
            $isValid = false;
        } elseif (empty($returnlessSignature)) {
            $this->setResponse("Can't find header: '" . self::HEADER_SIGNATURE . "'", 401, true);
            $isValid = false;
        } else {
            $integrationApiPassword = $this->config->getApiPassword();
            $hashedSignature = hash_hmac("sha256" , $incrementId , $integrationApiPassword);
            if ($returnlessSignature != $hashedSignature) {
                $this->setResponse("Signature is not valid!", 403, true);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
