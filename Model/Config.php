<?php

namespace Returnless\Connector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * const CONFIG_PATH_API_ENABLED
     */
    const CONFIG_PATH_API_ENABLED = 'returnless_connector/general/enabled';

    /**
     * const CONFIG_PATH_API_PASSWORD
     */
    const CONFIG_PATH_API_PASSWORD = 'returnless_connector/general/integration_api_password';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getEnabled($store = null)
    {
        $enabled = (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_API_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $enabled;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getApiPassword($store = null)
    {
        $apiPassword = (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_API_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $apiPassword;
    }
}
