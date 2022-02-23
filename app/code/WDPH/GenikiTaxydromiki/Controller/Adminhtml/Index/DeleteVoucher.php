<?php
namespace WDPH\GenikiTaxydromiki\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use WDPH\GenikiTaxydromiki\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use WDPH\GenikiTaxydromiki\Model\VoucherFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\Product;

class DeleteVoucher extends \Magento\Backend\App\Action
{
    protected $helper;
    protected $soapClient;
    protected $authKey;
    protected $orderRepository;
    protected $voucherFactory;    
    protected $resultRedirect;
    protected $stockRegistry;
    protected $product;
 
    public function __construct(
        Context $context,        
        Data $helper,
        OrderRepositoryInterface $orderRepository,
        VoucherFactory $voucherFactory,
        StockRegistryInterface $stockRegistry,
        Product $product
    )
    {
        parent::__construct($context);
        $this->helper = $helper;        
        $this->orderRepository = $orderRepository;
        $this->voucherFactory = $voucherFactory;
        $this->stockRegistry = $stockRegistry;            
        $this->soapClient = $this->helper->getSoapClient();
        $this->product = $product;

        $this->resultRedirect = $this->resultRedirectFactory->create();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('WDPH_GenikiTaxydromiki::delete');
    }
 
    public function execute()
    {
        $this->messageManager->addErrorMessage(__('Delete action isn\'t ready.'));
        return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());

        $orderId = $this->getRequest()->getParam('order_id');
        if(!$orderId)
        {
            $this->messageManager->addErrorMessage(__('ERROR: NO ORDER ID PARAMETER!'));            
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $order = $this->orderRepository->get($orderId);
        if(!$order)
        {
            $this->messageManager->addErrorMessage(__('ERROR: NO ORDER! ID: %1', $orderId));            
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        if($order->getShippingMethod() != $this->helper->getConfig('general/delivery_method'))
        {
            $this->messageManager->addErrorMessage(__('ERROR: WRONT DELIVERY METHOD! ID: %1', $order->getIncrementId()));
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }

        /*$items = $order->getAllVisibleItems();
        foreach($items as $item) {
            $qtyShipped = $item->getQtyShipped();
            if($item->getProduct()->getTypeId() == 'configurable')
            {
                foreach($item->getChildrenItems() as $childItem)
                {
                    //echo $childItem->getProduct()->getSku() . '<br>';
                    $childProductStockItem = $this->stockRegistry->getStockItem($childItem->getProduct()->getId());                    
                    echo (float)($childProductStockItem->getData('qty')) + (float)$qtyShipped . '<br>';
                    //$childProductStockItem->setData('qty', (float)($childProductStockItem->getData('qty')) + (float)$qtyShipped);
                    //$childProductStockItem->setData('is_in_stock', true);
                    //$childProductStockItem->save();
                    //echo $childProductStockItem->getData('qty');
                }
            }
            
            
            $stockItem->save();
            echo 'SHIPPED QTY: ' . $qtyShipped . '<br>';
            $product = $this->product->load($item->getProductId());
            if($product->getTypeId() !== 'simple') continue;
            echo $product->getSku() . '<br>';
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $productQty = $stockItem->getData('qty');
            echo 'PRODUCT QTY: ' . $productQty . '<br>';
        }
        die();*/


        if(!$this->authKey)
        {
            $this->authKey = $this->helper->authenticate($this->soapClient);
        }
        $pod = $order->getTracksCollection()->getFirstItem()->getNumber();
        if(!$pod)
        {
            $this->messageManager->addErrorMessage(__('ERROR: Order %1 has no POD', $order->getIncrementId()));            
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $voucher = $this->voucherFactory->create()->load($pod, 'pod');
        if(!$voucher)
        {
            $this->messageManager->addErrorMessage(__('ERROR: No voucher records been found with the POD: %1 Order: %2', $pod, $order->getIncrementId()));            
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $params = array(
            'sAuthKey' 	=> $this->authKey,
            'nJobId' 	=> $voucher->getData('jobid'),
            'bCancel' 	=> true
        );
        try
        {
            $response = $this->soapClient->CancelJob($params);
        }
        catch (\Exception $e)
        {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }        

        if($response->CancelJobResult != 0)
        {
            $this->messageManager->addError(__('SOAP ERROR: CancelJob() returned result code: %1', $response->CancelJobResult));            
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        
        $shipments = $order->getShipmentsCollection();
        foreach($shipments as $shipment)
        {
            $shipment->delete();
        }

        $invoices = $order->getInvoiceCollection();
        foreach($invoices as $invoice)
        {
            $items = $invoice->getAllItems();
            foreach ($items as $i) {
                $i->delete();
            }
            $invoice->delete();
        }

        $items = $order->getAllVisibleItems();
        foreach($items as $item) {
            /*$qtyShipped = $item->getQtyShipped();
            if($item->getProduct()->getTypeId() == 'configurable')
            {
                foreach($item->getChildrenItems() as $childItem)
                {                    
                    $childProductStockItem = $this->stockRegistry->getStockItem($childItem->getProduct()->getId());                                        
                    $childProductStockItem->setData('qty', (float)($childProductStockItem->getData('qty')) + (float)$qtyShipped);
                    $childProductStockItem->setData('is_in_stock', true);
                    $childProductStockItem->save();                    
                }
            }*/
            $item->setQtyShipped(0);
            $item->setQtyInvoiced(0);
            $item->save();
        }            
        
        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $order->setState($orderState)->setStatus($orderState);
        $order->save();

        $data = array(
            'status' => 'Cancelled',
        );
        $voucher->addData($data)->save();
        $this->messageManager->addSuccessMessage(__('Order: %1. Voucher successfully deleted.', $order->getIncrementId()));
        return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
    }
}
?>