<?php
namespace WDPH\GenikiTaxydromiki\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use WDPH\GenikiTaxydromiki\Helper\Data;
use WDPH\GenikiTaxydromiki\Model\VoucherFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use WDPH\GenikiTaxydromiki\Model\CreateVoucher as OrderHelper;

class MassCreatePrintVoucher extends AbstractMassAction
{
    protected $authKey;
    protected $soapClient;
    protected $helper;
    protected $orderHelper;
    protected $voucherFactory; 

    public function __construct(
        Context $context,
        Filter $filter,        
        Data $helper,
        VoucherFactory $voucherFactory,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper     
    )
    {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;      
        $this->helper = $helper;
        $this->orderHelper = $orderHelper;
        $this->voucherFactory = $voucherFactory; 
        $this->soapClient = $this->helper->getSoapClient();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('WDPH_GenikiTaxydromiki::create');
    }

    protected function massAction(AbstractCollection $collection)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $pods = array();
        foreach ($collection->getItems() as $order)
        {
            if($order->getShippingMethod() != $this->helper->getConfig('general/delivery_method'))
            {
                $this->messageManager->addErrorMessage(__('ERROR: WRONT DELIVERY METHOD! ID: %1', $order->getIncrementId()));
                continue;
            }          
            if(!$order->canShip())
            {
                $this->messageManager->addErrorMessage(__('Order %1 cannot be shipped or has already been shipped', $order->getIncrementId()));                
                continue;
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
                $skipFlag = true;
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
                $this->messageManager->addErrorMessage(__('Order %2. SOAP ERROR: CreateJob() returned result code: %1', $response->CreateJobResult->Result, $order->getIncrementId()));                
                continue;
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

            $pods[] = $response->CreateJobResult->Voucher;

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
                    
                    $pods[] = $subvoucher->VoucherNo;
                    
                    $subvoucher_data = array();
                }
            }           
            $this->messageManager->addSuccessMessage(__('Order: %1. Voucher successfully created.', $order->getIncrementId()));
        }
        if(empty($pods))
        {
            $this->messageManager->addErrorMessage(__('Nothing to print :('));
            return $resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        return $resultRedirect->setUrl(str_replace("?WSDL", "", $this->helper->getConfig('login/api_url')) . '/GetVouchersPdf?authKey=' . urlencode($this->authKey) . '&voucherNumbers=' . implode('&voucherNumbers=', $pods) . '&Format=Flyer&extraInfoFormat=None');        
    }
}
?>