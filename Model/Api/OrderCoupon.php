<?php

namespace Returnless\Connector\Model\Api;

use Returnless\Connector\Api\OrderCouponInterface;
use Psr\Log\LoggerInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\Framework\Exception\InputException;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Model\Coupon\Massgenerator;
use Magento\Framework\Module\ResourceInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

/**
 * Interface OrderCoupon
 */
class OrderCoupon implements OrderCouponInterface
{
    /**
     * const NAMESPACE_MODULE
     */
    const NAMESPACE_MODULE = 'Returnless_Connector';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CouponRepositoryInterface
     */
    protected $couponRepository;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var RuleInterfaceFactory
     */
    protected $rule;

    /**
     * @var CouponInterface
     */
    protected $coupon;

    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var Massgenerator
     */
    protected $couponGenerator;

    /**
     * @var CustomerGroup
     */
    protected $customerGroup;

    /**
     * @var WebsiteCollectionFactory
     */
    protected $websiteCollection;

    /**
     * OrderCoupon constructor.
     * @param CouponRepositoryInterface $couponRepository
     * @param RuleRepositoryInterface $ruleRepository
     * @param RuleInterfaceFactory $rule
     * @param CouponInterface $coupon
     * @param Massgenerator $couponGenerator
     * @param ResourceInterface $moduleResource
     * @param CustomerGroup $customerGroup
     * @param WebsiteCollectionFactory $websiteCollection
     * @param LoggerInterface $logger
     */
    public function __construct(
        CouponRepositoryInterface $couponRepository,
        RuleRepositoryInterface $ruleRepository,
        RuleInterfaceFactory $rule,
        CouponInterface $coupon,
        Massgenerator $couponGenerator,
        ResourceInterface $moduleResource,
        CustomerGroup $customerGroup,
        WebsiteCollectionFactory $websiteCollection,
        LoggerInterface $logger
    ) {
        $this->couponRepository = $couponRepository;
        $this->ruleRepository = $ruleRepository;
        $this->rule = $rule;
        $this->coupon = $coupon;
        $this->couponGenerator = $couponGenerator;
        $this->moduleResource = $moduleResource;
        $this->customerGroup = $customerGroup;
        $this->websiteCollection = $websiteCollection;
        $this->logger = $logger;
    }

    /**
     * Create Coupon by Rule id.
     *
     * @param int $ruleId
     *
     * @return int|null
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCoupon(int $ruleId) {
        /** @var CouponInterface $coupon */
        $coupon = $this->coupon;
        $couponCode = $this->couponGenerator->setLength(12)->generateCode();
        $coupon->setCode($couponCode)
            ->setIsPrimary(1)
            ->setRuleId($ruleId);

        /** @var CouponRepositoryInterface $couponRepository */
        $coupon = $this->couponRepository->save($coupon);
        return $couponCode;
    }

    /**
     * @inheritdoc
     */
    public function createCouponReturnless($requestParams)
    {
        $response['installed_module_version'] = $this->moduleResource->getDbVersion(self::NAMESPACE_MODULE);

        $newRule = $this->rule->create();
        $newRule->setName($requestParams['order_id']. ' | ' . $requestParams['coupon_amount'] . ' | ' .
            $requestParams['customer_email'])
            ->setDescription("Coupon code for return " . $requestParams['return_id'])
            ->setIsAdvanced(true)
            ->setStopRulesProcessing(false)
            ->setCustomerGroupIds($this->getCustomerGroups())
            ->setWebsiteIds($this->getWebsiteIds())
            ->setIsRss(0)
            ->setUsesPerCustomer(1)
            ->setUsesPerCoupon(1)
            ->setDiscountStep(0)
            ->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON)
            ->setSimpleAction(RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT_FOR_CART)
            ->setDiscountAmount($requestParams['coupon_amount'])
            ->setIsActive(true);

        try {
            $ruleCreate = $this->ruleRepository->save($newRule);
            if ($ruleCreate->getRuleId()) {
                $response['result']['coupon_id'] = $ruleCreate->getRuleId();
                $response['result']['coupon_code'] = $this->createCoupon($ruleCreate->getRuleId());
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
        return $response;
    }

    /**
     * @return array
     */
    protected function getCustomerGroups()
    {
        $groupIds = [];
        $customerGroups = $this->customerGroup->getItems();
        foreach ($customerGroups as $customerGroup) {
            $groupIds[] = $customerGroup->getCustomerGroupId();
        }
        return $groupIds;
    }

    /**
     * @return array
     */
    protected function getWebsiteIds()
    {
        $websiteIds = [];
        $websites = $this->websiteCollection->create();
        foreach ($websites as $website) {
            $websiteIds[] = $website->getId();
        }

        return $websiteIds;
    }
}
