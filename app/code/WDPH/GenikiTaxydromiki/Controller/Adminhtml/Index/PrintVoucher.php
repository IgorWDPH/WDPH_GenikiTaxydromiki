<?php
namespace WDPH\GenikiTaxydromiki\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use WDPH\GenikiTaxydromiki\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;

class PrintVoucher extends \Magento\Backend\App\Action
{    
    protected $helper;
    protected $soapClient;
    protected $authKey;
    protected $orderRepository;    
 
    public function __construct(
        Context $context,        
        Data $helper,
        OrderRepositoryInterface $orderRepository        
    )
    {
        parent::__construct($context);
        $this->helper = $helper;        
        $this->orderRepository = $orderRepository;        
        $this->soapClient = $this->helper->getSoapClient();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('WDPH_GenikiTaxydromiki::print');
    }
 
    public function execute()
    {
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
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
        if(!$this->authKey)
        {
            $this->authKey = $this->helper->authenticate($this->soapClient);
        }        
        $redirect->setUrl(str_replace('?WSDL', '', strval($this->helper->getConfig('login/api_url'))) . '/GetVouchersPdf?authKey='.urlencode($this->authKey) . '&voucherNumbers=' . $order->getTracksCollection()->getFirstItem()->getNumber() . '&Format=Flyer&extraInfoFormat=None');
        return $redirect;
    }
}
?>