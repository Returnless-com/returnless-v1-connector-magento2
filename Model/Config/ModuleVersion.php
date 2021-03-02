<?php

namespace Returnless\Connector\Model\Config;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ResourceInterface;

/**
 * Class ModuleVersion
 * @package Returnless\Connector\Model\Config
 */
class ModuleVersion extends Value
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
     * ModuleVersion constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ResourceInterface $moduleResource
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ResourceInterface $moduleResource,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->moduleResource = $moduleResource;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Inject current installed module version as the config value.
     *
     * @return void
     */
    public function afterLoad()
    {
        $moduleVersion = $this->moduleResource->getDbVersion(self::NAMESPACE_MODULE);

        $this->setValue($moduleVersion);
    }
}
