<?php
namespace Returnless\Connector\Model\Api;

use Returnless\Connector\Api\OrderCreditMemoInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Returnless\Connector\Helper\Data as RetHelper;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;

/**
 * Class OrderCreditMemo
 */
class OrderCreditMemo implements OrderCreditMemoInterface
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
     * @var WebsiteCollectionFactory
     */
    protected $websiteCollection;

    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var RetHelper
     */
    protected $retHelper;

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var CreditmemoSender
     */
    protected $creditMemoSender;

    /**
     * @var CreditmemoLoader
     */
    protected $creditMemoLoader;

    /**
     * OrderCoupon constructor.
     *
     * @param CreditmemoSender $creditMemoSender
     * @param CreditmemoLoader $creditMemoLoader
     * @param CreditmemoFactory $creditMemoFactory
     * @param CreditmemoManagementInterface $creditMemoService
     * @param Invoice $invoice
     * @param RetHelper $retHelper
     * @param WebsiteCollectionFactory $websiteCollection
     * @param LoggerInterface $logger
     * @param ResourceInterface $moduleResource
     */
    public function __construct(
        CreditmemoSender $creditMemoSender,
        CreditmemoLoader $creditMemoLoader,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoManagementInterface $creditMemoService,
        Invoice $invoice,
        RetHelper $retHelper,
        WebsiteCollectionFactory $websiteCollection,
        LoggerInterface $logger,
        ResourceInterface $moduleResource
    ) {
        $this->creditMemoSender = $creditMemoSender;
        $this->creditMemoLoader = $creditMemoLoader;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoService = $creditMemoService;
        $this->invoice = $invoice;
        $this->retHelper = $retHelper;
        $this->websiteCollection = $websiteCollection;
        $this->logger = $logger;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @inheritdoc
     */
    public function createCreditMemo($requestParams)
    {
        $response['installed_module_version'] = $this->moduleResource->getDbVersion(self::NAMESPACE_MODULE);
        $response['return_message'] = 'Success!';
        $response['code'] = 200;

        $order = $this->retHelper->searchOrder($requestParams['order_id']);

        if (!$order->hasInvoices()) {
            $response['code'] = 404;
            $response['return_message'] = __("Order is not invoiced.");

            return $response;
        }

        $orderId = $order->getId();
        if (!$orderId) {
            $response['code'] = 404;
            $response['return_message'] = __("Order is not found.");

            return $response;
        }

        $creditMemoData = [];
        $itemToCredit = [];

        $creditMemoData['shipping_amount'] = 0;
        if (isset($requestParams['shipping_amount'])
            && !empty($requestParams['shipping_amount'])
        ) {
            $creditMemoData['shipping_amount'] = (float) $requestParams['shipping_amount'];
        }

        $creditMemoData['adjustment_positive'] = 0;
        if (isset($requestParams['adjustment_positive'])
            && !empty($requestParams['adjustment_positive'])
        ) {
            $creditMemoData['adjustment_positive'] = (float) $requestParams['adjustment_positive'];
        }

        $creditMemoData['adjustment_negative'] = 0;
        if (isset($requestParams['adjustment_negative'])
            && !empty($requestParams['adjustment_negative'])
        ) {
            $creditMemoData['adjustment_negative'] = (float) $requestParams['adjustment_negative'];
        }

        $creditMemoData['do_offline'] = 1;
        if (isset($requestParams['payment_refund'])
            && $requestParams['payment_refund'] == 'on'
        ) {
            $creditMemoData['do_offline'] = 0;
        }

        $creditMemoData['comment_text'] = '';
        if (isset($requestParams['comment_text'])
            && !empty($requestParams['comment_text'])
            && is_string($requestParams['comment_text'])
        ) {
            $creditMemoData['comment_text'] = $requestParams['comment_text'];
        }

        $creditMemoData['send_email'] = 0;
        if (isset($requestParams['email_to_customer'])
            && $requestParams['email_to_customer'] == 'on'
        ) {
            $creditMemoData['send_email'] = 1;
        }

        foreach ($requestParams['items'] as $requestItem) {
            $item = $this->retHelper->getItemBySku($order, $requestItem['sku']);

            if (!$item) {
                $response['code'] = 404;
                $response['return_message'] = __("Sku is not associated to Order.");

                return $response;
            }

            $orderItemId = $item->getId();

            $itemToCredit[$orderItemId] = [
                'qty' => (float) $requestItem['qty']
            ];
        }
        $creditMemoData['items'] = $itemToCredit;

        try {
            $this->creditMemoLoader->setOrderId($orderId); //pass order id
            $this->creditMemoLoader->setCreditmemo($creditMemoData);

            $invoiceCollection = $order->getInvoiceCollection();
            foreach ($invoiceCollection as $invoice) {
                $state = $invoice->getState();

                if ($state != 2) {
                    continue;
                }

                $invoiceId = $invoice->getId();
                $this->creditMemoLoader->setInvoiceId($invoiceId);
            }

            $creditMemo = $this->creditMemoLoader->load();
            if ($creditMemo) {
                if (!$creditMemo->isValidGrandTotal()) {
                    $response['code'] = 406;
                    $response['return_message'] = __('The credit memo\'s total must be positive.');

                    return $response;
                }

                if (!empty($creditMemoData['comment_text'])) {
                    $creditMemo->addComment(
                        $creditMemoData['comment_text'],
                        isset($creditMemoData['comment_customer_notify']),
                        isset($creditMemoData['is_visible_on_front'])
                    );

                    $creditMemo->setCustomerNote($creditMemoData['comment_text']);
                    $creditMemo->setCustomerNoteNotify(isset($creditMemoData['comment_customer_notify']));
                }

                $creditMemo->getOrder()->setCustomerNoteNotify(!empty($creditMemoData['send_email']));
                $this->creditMemoService->refund($creditMemo, (bool)$creditMemoData['do_offline']);

                if (!empty($creditMemoData['send_email'])) {
                    $this->creditMemoSender->send($creditMemo);
                }

                if ($creditMemo->getEntityId()) {
                    $entityId = $creditMemo->getEntityId();
                    $response['result']['entity_id'] = $entityId;
                }

                if ($creditMemo->getSubtotal()) {
                    $subtotal = $creditMemo->getSubtotal();
                    $response['result']['subtotal'] = $subtotal;
                }

                if ($creditMemo->getGrandTotal()) {
                    $grandTotal = $creditMemo->getGrandTotal();
                    $response['result']['grand_total'] = $grandTotal;
                }
            }
        } catch (\Exception $exception) {
            $response['code'] = 406;

            $this->logger->error($exception->getMessage());
            $response['return_message'] = $exception->getMessage();
        }

        return $response;
    }
}
