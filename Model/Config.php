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
     * const CONFIG_EAN_ATTRIBUTE_CODE
     */
    const CONFIG_EAN_ATTRIBUTE_CODE = 'returnless_connector/general/u_upc';

    /**
     * const CONFIG_BRAND_ATTRIBUTE_CODE
     */
    const CONFIG_BRAND_ATTRIBUTE_CODE = 'returnless_connector/general/u_brand';

    /**
     * const CONFIG_SEPARATE_BUNDLE_PRODUCTS
     */
    const CONFIG_SEPARATE_BUNDLE_PRODUCTS = 'returnless_connector/general/bundle_enabled';

    /**
     * const Path to config enabled marketplace search
     */
    const CONFIG_MARKETPLACE_SEARCH_ENABLED = 'returnless_connector/marketplace_orders/enabled';

    /**
     * const Path to config marketplace integration partner
     */
    const CONFIG_MARKETPLACE_SEARCH_VENDOR_ID = 'returnless_connector/marketplace_orders/integration_partner';

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
    public function getMarketplaceSearchEnabled($store = null)
    {
        $enabled = (string)$this->scopeConfig->getValue(
            self::CONFIG_MARKETPLACE_SEARCH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $enabled;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getMarketplaceSearchPartnerId($store = null)
    {
        $enabled = (string)$this->scopeConfig->getValue(
            self::CONFIG_MARKETPLACE_SEARCH_VENDOR_ID,
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

    /**
     * @param null $store
     * @return string
     */
    public function getEanAttributeCode($store = null)
    {
        $eanAttributeCode = (string)$this->scopeConfig->getValue(
            self::CONFIG_EAN_ATTRIBUTE_CODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $eanAttributeCode;
    }

    /**
     * Enabled separate bundle products
     *
     * @param null $store
     * @return string
     */
    public function getSeparateBundle($store = null)
    {
        $separateBundle = (string)$this->scopeConfig->getValue(
            self::CONFIG_SEPARATE_BUNDLE_PRODUCTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $separateBundle;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getBrandAttributeCode($store = null)
    {
        $brandttributeCode = (string)$this->scopeConfig->getValue(
            self::CONFIG_BRAND_ATTRIBUTE_CODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $brandttributeCode;
    }
}
