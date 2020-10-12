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
 * How to check logs: grep -rn 'returnless' var/log/debug.log
 */
class OrderInfo implements OrderInfoInterface
{
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

        $this->logger->debug('Increment Id', [$incrementId]);

        try {
            $order = $this->getOrderByIncrementId($incrementId);

            $orderProducts['id'] = $order->getIncrementId();
            $orderProducts['order_id'] = $order->getEntityId();
            $orderProducts['create_at']['value'] = $order->getCreatedAt();

            $orderProducts['customer']['id'] = $order->getCustomerId();
            $orderProducts['customer']['email'] = $order->getCustomerEmail();

            $this->logger->debug('Order Id', [$orderProducts['order_id']]);
            $this->logger->debug('Customer Email', [$orderProducts['customer']['email']]);

            $orderItems = $order->getAllVisibleItems();

            $this->logger->debug('Order has items', [count($orderItems)]);

            foreach ($orderItems as $orderItemKey => $orderItem) {
                $orderProducts['order_products'][$orderItemKey]['product_id'] = $orderItem->getProductId();
                $orderProducts['order_products'][$orderItemKey]['quantity'] = $orderItem->getQtyOrdered();
                $orderProducts['order_products'][$orderItemKey]['order_product_id'] = $orderItem->getItemId();
                $orderProducts['order_products'][$orderItemKey]['price_inc_tax'] = $orderItem->getPriceInclTax();
                $orderProducts['order_products'][$orderItemKey]['model'] = $orderItem->getSku();

                $product = $this->getProductById($orderItem->getProductId());

                $orderProducts['order_products'][$orderItemKey]['name'] = $product->getName();
                $orderProducts['order_products'][$orderItemKey]['images'][0]['http_path'] = $this->getImageByProduct($product);
                $orderProducts['order_products'][$orderItemKey]['images'][1]['http_path'] = $this->getImageByProduct1($product);
                $orderProducts['order_products'][$orderItemKey]['url'] = $product->getProductUrl();
                $orderProducts['order_products'][$orderItemKey]['categories_ids'] = $product->getCategoryIds();
                $orderProducts['order_products'][$orderItemKey]['u_brand'] = $product->getBrand();
            }

            $response['return_code'] = 0;
            $response['result'] = $orderProducts;
        } catch (\Exception $e) {
            $response['return_message'] = $e->getMessage();
            $this->logger->debug($e->getMessage());
        }

        $this->returnResult($response);
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
