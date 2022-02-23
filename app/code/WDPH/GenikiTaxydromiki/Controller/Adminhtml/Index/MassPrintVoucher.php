<?php
namespace WDPH\GenikiTaxydromiki\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use WDPH\GenikiTaxydromiki\Helper\Data;

class MassPrintVoucher extends AbstractMassAction
{
    protected $authKey;
    protected $helper;
    protected $soapClient;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Data $helper
    )
    {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
        $this->soapClient = $this->helper->getSoapClient();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('WDPH_GenikiTaxydromiki::print');
    }

    protected function massAction(AbstractCollection $collection)
    {
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $pods = array();
        foreach ($collection->getItems() as $order)
        {
            if($order->getShippingMethod() != $this->helper->getConfig('general/delivery_method'))
            {
                $this->messageManager->addErrorMessage(__('ERROR: WRONT DELIVERY METHOD! ID: %1', $order->getIncrementId()));
                return $redirect->setPath($this->_redirect->getRefererUrl());
            }
            if(!$this->authKey)
            {
                $this->authKey = $this->helper->authenticate($this->soapClient);
            }
            $pod = $order->getTracksCollection()->getFirstItem()->getNumber();
            if(!$pod)
            {
                $this->messageManager->addErrorMessage(__('Order %1 has no voucher created.', $order->getIncrementId()));
                return $redirect->setPath($this->_redirect->getRefererUrl());
            }
            $pods[] = $pod;
        }        
        $redirect->setUrl(str_replace("?WSDL", "", $this->helper->getConfig('login/api_url')) . '/GetVouchersPdf?authKey=' . urlencode($this->authKey) . '&voucherNumbers=' . implode('&voucherNumbers=', $pods) . '&Format=Flyer&extraInfoFormat=None');
        return $redirect;
    }
}
?>