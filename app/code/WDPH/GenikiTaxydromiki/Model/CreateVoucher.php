<?php
namespace WDPH\GenikiTaxydromiki\Model;

use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Shipping\Model\ShipmentNotifier;

class CreateVoucher
{
    protected $convertOrder;
	protected $trackFactory;
	protected $invoiceService;
	protected $transaction;
	protected $invoiceSender;

    public function __construct(             
        InvoiceService $invoiceService,
		TrackFactory $trackFactory,
		ConvertOrder $convertOrder,
		InvoiceSender $invoiceSender,
		Transaction $transaction,
        ShipmentNotifier $shipmentNotifier
    )
	{
        $this->convertOrder = $convertOrder;
		$this->trackFactory = $trackFactory;
		$this->invoiceService = $invoiceService;
		$this->transaction = $transaction;
		$this->invoiceSender = $invoiceSender;
        $this->shipmentNotifier = $shipmentNotifier;
    }

    public function createShipment($order, $voucher, $messageManager)
    {
        // Check if order can be shipped or has already shipped
        if(!$order->canShip())
        {
            $messageManager->addErrorMessage(__('Order: %1. Impossible to create a shipment.', $order->getIncrementId()));
            return false;
        }
        $shipment = $this->convertOrder->toShipment($order);
        // Loop through order items
        foreach($order->getAllItems() as $orderItem)
        {
            // Check if order item has qty to ship or is virtual
            if(!$orderItem->getQtyToShip() || $orderItem->getIsVirtual())
            {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        //SET TRACKING NUMBER
        $data = array(
            'carrier_code' => 'custom',
            'title' => 'Γενική Ταχυδρομική',
            'number' => $voucher,
        );        
        $track = $this->trackFactory->create()->addData($data);
        $shipment->addTrack($track);

        // Register shipment
        $shipment->register();
        
        $shipment->getOrder()->setIsInProcess(true);

        try
        {
            // Save created shipment and order
            $shipment->save();
            $shipment->getOrder()->save();
            // Send email
            $this->shipmentNotifier->notify($shipment);        
            $shipment->save();
            return true;
        }
        catch (\Exception $e)
        {
            $messageManager->addErrorMessage(__('Order: %2. ERROR: %1', $e->getMessage(), $order->getIncrementId()));
            return false;
            //throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

	public function createInvoice($order, $messageManager)
    {
        if($order->hasInvoices())
        {
            $messageManager->addNoticeMessage(__('Order: %1 already has invoices.', $order->getIncrementId()));
            return true;
        }
        if(!$order->canInvoice())
        {
            $messageManager->addErrorMessage(__('Order: %1. Impossible to create an invoice.', $order->getIncrementId()));
            return false;
        }
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->save();
        $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();
        $this->invoiceSender->send($invoice);
        //Send Invoice mail to customer
        $order->addStatusHistoryComment(__('Notified customer about invoice creation %1.', $invoice->getId()))
            ->setIsCustomerNotified(true)
            ->save();
        $order->save();
        return true;
    }
}