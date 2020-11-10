<?php

namespace Returnless\Connector\Model\Api;

use Returnless\Connector\Api\OrderInfoInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Helper\Image;

/**
 * Interface OrderInfo
 *
 * How to check logs: grep -rn 'returnless' var/log/system.log
 */
class OrderInfo implements OrderInfoInterface
{
    /**
     * @var bool
     */
    protected $returnFlag = false;

    /**
     * @var LoggerInterface
     *
     */
    protected $logger;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var Image
     */
    protected $image;

    /**
     * OrderInfo constructor.
     *
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param Image $image
     */
    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        Image $image
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger->withName('returnless');
        $this->image = $image;
    }

    /**
     * @inheritdoc
     */
    public function getOrderInfoReturnless($incrementId)
    {
        $response['return_code'] = 112;
        $response['return_message'] = '';
        $orderInfo = [];

        $this->logger->debug('Increment Id', [$incrementId]);

        try {
            $order = $this->getOrderByIncrementId($incrementId);

            $orderInfo['id'] = $order->getIncrementId();
            $orderInfo['order_id'] = $order->getEntityId();
            $orderInfo['create_at']['value'] = $order->getCreatedAt();

            $orderInfo['customer']['id'] = $order->getCustomerId();
            $orderInfo['customer']['email'] = $order->getCustomerEmail();

            $this->logger->debug('Order Id', [$orderInfo['order_id']]);
            $this->logger->debug('Customer Email', [$orderInfo['customer']['email']]);

            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $orderInfo['billing_address']['first_name'] = $billingAddress->getFirstname();
                $orderInfo['billing_address']['last_name'] = $billingAddress->getLastname();
                $orderInfo['billing_address']['postcode'] = $billingAddress->getPostcode();
                $orderInfo['billing_address']['city'] = $billingAddress->getCity();
                $street = $billingAddress->getStreet();
                $orderInfo['billing_address']['address1'] = isset($street[0]) ? $street[0] : '';
                $orderInfo['billing_address']['address2'] = isset($street[1]) ? $street[1] : '';
                $orderInfo['billing_address']['addition'] = isset($street[2]) ? $street[2] : '';
            }

            $orderItems = $order->getAllVisibleItems();

            $this->logger->debug('Order has items', [count($orderItems)]);

            foreach ($orderItems as $orderItemKey => $orderItem) {
                $orderInfo['order_products'][$orderItemKey]['product_id'] = $orderItem->getProductId();
                $orderInfo['order_products'][$orderItemKey]['quantity'] = $orderItem->getQtyOrdered();
                $orderInfo['order_products'][$orderItemKey]['order_product_id'] = $orderItem->getItemId();
                $orderInfo['order_products'][$orderItemKey]['price_inc_tax'] = $orderItem->getPriceInclTax();
                $orderInfo['order_products'][$orderItemKey]['model'] = $orderItem->getSku();

                $product = $this->getProductById($orderItem->getProductId());

                $orderInfo['order_products'][$orderItemKey]['name'] = $product->getName();
                $orderInfo['order_products'][$orderItemKey]['images'][0]['http_path'] = $this->getImageByProduct($product);
                $orderInfo['order_products'][$orderItemKey]['images'][1]['http_path'] = $this->getImageByProduct1($product);
                $orderInfo['order_products'][$orderItemKey]['url'] = $product->getProductUrl();
                $orderInfo['order_products'][$orderItemKey]['categories_ids'] = $product->getCategoryIds();
                $orderInfo['order_products'][$orderItemKey]['u_brand'] = $product->getBrand();
            }

            $response['return_code'] = 0;
            $response['result'] = $orderInfo;
        } catch (\Exception $e) {
            $response['return_message'] = $e->getMessage();
            $this->logger->debug($e->getMessage());
        }

        if ($this->returnFlag) {
            return $response;
        }

        $this->returnResult($response);
    }

    /**
     * This method provides an ability to return Response Data
     *
     * @return $this
     */
    public function setReturnFlag()
    {
        $this->returnFlag = true;

        return $this;
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * @param $product
     * @return mixed
     */
    public function doesProductHaveImage($product)
    {
        $image = false;

        if (!$product || !$product->getId()) {
            return $image;
        }

        $image = $product->getImage();

        if (empty($image) || $image == 'no_selection') {
            $image = $product->getSmallImage();
        }

        if (empty($image) || $image == 'no_selection') {
            $image = $product->getThumbnail();
        }

        return (empty($image) || $image == 'no_selection') ? false : true;
    }

    /**
     * @param $product
     * @return bool
     */
    public function getImageByProduct1($product)
    {
        $image = false;

        $productHasImage = $this->doesProductHaveImage($product);

        if ($productHasImage) {
            $mediaGalleryImages = $product->getMediaGalleryImages();

            $image = $mediaGalleryImages->getFirstItem()->getUrl();
        }

        return $image;
    }

    public function getImageByProduct($product)
    {
        $image = false;

        $productHasImage = $this->doesProductHaveImage($product);

        if ($productHasImage) {
            $image = $this->image
                ->init($product, 'product_page_image_large')
                ->setImageFile($product->getImage())
                ->getUrl();
        }

        return $image;
    }

    /**
     * @param $incrementId
     * @return mixed
     */
    protected function getOrderByIncrementId($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'increment_id',
                $incrementId,
                'eq'
            )
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getFirstItem();
    }

    /**
     * @param $result
     */
    protected function returnResult($result)
    {
        header("Content-Type: application/json; charset=utf-8");

        $result = json_encode($result);
        print_r($result,false);

        die();
    }
}
