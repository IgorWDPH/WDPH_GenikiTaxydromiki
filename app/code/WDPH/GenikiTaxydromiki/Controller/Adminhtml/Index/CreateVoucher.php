<?php
namespace WDPH\GenikiTaxydromiki\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use WDPH\GenikiTaxydromiki\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use WDPH\GenikiTaxydromiki\Model\VoucherFactory;
use WDPH\GenikiTaxydromiki\Model\CreateVoucher as OrderHelper;

class CreateVoucher extends \Magento\Backend\App\Action
{    
    protected $helper;
    protected $orderRepository;
    protected $voucherFactory;    
    protected $soapClient;
    protected $authKey;    
    protected $resultRedirect;
    protected $orderHelper;
 
    public function __construct(
        Context $context,        
        Data $helper,
        OrderRepositoryInterface $orderRepository,
        VoucherFactory $voucherFactory,        
        OrderHelper $orderHelper      
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;        
        $this->voucherFactory = $voucherFactory;                      
        $this->soapClient = $this->helper->getSoapClient();
        $this->orderHelper = $orderHelper;
        
        $this->resultRedirect = $this->resultRedirectFactory->create();        
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('WDPH_GenikiTaxydromiki::create');
    }
 
    public function execute()
    {        
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
        if(!$order->canShip())
        {
            $this->messageManager->addErrorMessage(__('Order %1 cannot be shipped or has already been shipped', $order->getIncrementId()));
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $codAmount = 0;
        $services = array();
        //TODO: add admin config with payment method selection
        if($order->getPayment()->getMethodInstance()->getCode() == 'checkmo')
        {
            $services[] = 'ΑΜ';
            $codAmount = $order->getGrandTotal();
        }
        /*if(!$order->canInvoice())
        {
            $this->messageManager->addErrorMessage(__('Order %1 cannot be invoiced or has already been invoiced', $order->getIncrementId()));
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }*/
		$comment = $order->getData('sparsh_order_comments');
		if($comment) $comment = __('Customer Comment:') . ' ' . $comment;
        $orderData = $order->getShippingAddress()->getData();
        $params = array(
            'ReceivedDate'				=> date('Y-m-d'),
            'Name'						=> $order->getShippingAddress()->getName(),
            'Address'					=> trim($orderData['street']),
            'City'						=> $orderData['city'],
            'Telephone'					=> $orderData['telephone'],
            'Zip'						=> $orderData['postcode'],
            'Pieces'					=> 1,
            'Weight'					=> $this->helper->getConfig('general/weight'),
            'CodAmount'					=> $codAmount,
            'Comments'					=> $comment,
            'OrderId'					=> $order->getIncrementId(),
            'Services'					=> implode(',', $services),
            'InsAmount'					=> 0,
        );

        //SOAP Zend\Soap\Client
        if(!$this->authKey)
        {
            $this->authKey = $this->helper->authenticate($this->soapClient);
        }        
        $xml = array(
            'sAuthKey' => $this->authKey,
            'oVoucher' => $params,
            'eType' => 'Voucher'
        );
        $response = @$this->soapClient->CreateJob($xml);
        if($response->CreateJobResult->Result != 0)
        {            
            $this->messageManager->addErrorMessage(__('SOAP ERROR: CreateJob() returned result code: %1', $response->CreateJobResult->Result));
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }

        //CREATE INVOICE AND SHIPPMENT
        if(!$this->orderHelper->createInvoice($order, $this->messageManager) || !$this->orderHelper->createShipment($order, $response->CreateJobResult->Voucher, $this->messageManager))
        {
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }

        //CREATE VOUCHER RECORD
        $voucher = $this->voucherFactory->create();            
        $voucherData = array(
            'created_at'		=> date('d-m-Y H:i:s'),
            'pod'				=> $response->CreateJobResult->Voucher,
            'jobid'				=> $response->CreateJobResult->JobId,
            'orderno'			=> $order->getIncrementId(),
            'status'			=> 'Active',
            'is_printed'		=> 0,
        );
        $voucher->addData($voucherData);
        $voucher->save();

        // ADD SUBVOUCHERS
        if(isset($response->CreateJobResult->SubVouchers->Record) && count($response->CreateJobResult->SubVouchers->Record) > 0)
        {
            foreach($response->CreateJobResult->SubVouchers->Record as $subvoucher)
            {
                $subvoucher_data = array(
                    'created_at'		=> date('d-m-Y H:i:s'),
                    'pod'				=> $subvoucher->VoucherNo,
                    'jobid'				=> $response->CreateJobResult->JobId,
                    'orderno'			=> $order->getIncrementId(),
                    'status'			=> 'Active',
                    'is_printed'		=> 0,
                );
                $voucher = $this->voucherFactory->create();
                $voucher->addData($subvoucher_data);
                $voucher->save();                    
                $subvoucher_data = array();
            }
        }           
        $this->messageManager->addSuccessMessage(__('Order: %1. Voucher successfully created.', $order->getIncrementId()));
        return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
    }
}
?>