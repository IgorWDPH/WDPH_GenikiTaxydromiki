<?php
namespace WDPH\GenikiTaxydromiki\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;

    const XML_PATH_MEGAMENU = 'wdph_genikitaxydromiki_general/';

    public function __construct(Context $context)
	{
        parent::__construct($context);
    }

    public function getConfig($config_path, $storeCode = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MEGAMENU . $config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeCode);
    }

    public function getSoapClient()
    {
        return new \Zend\Soap\Client($this->getConfig('login/api_url'));
    }

    public function authenticate($soapClient)
	{
        try
        {
            $oAuthResult = $soapClient->Authenticate(
                array(
                    'sUsrName' 			=> strval($this->getConfig('login/username')),
                    'sUsrPwd' 			=> strval($this->getConfig('login/password')),
                    'applicationKey' 	=> strval($this->getConfig('login/appkey'))
                )
            );    
            if($oAuthResult->AuthenticateResult->Result != 0)
            {                
                throw new \Magento\Framework\Exception\LocalizedException(__('SOAP authentication error! Result code: ' . $oAuthResult->AuthenticateResult->Result));
                return false;
            }            
            return $oAuthResult->AuthenticateResult->Key;
        }
        catch (\Exception $e)
        {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
	}

    public function getOrderTractions($order)
    {        
        if(!$order || $order->getShippingMethod() != $this->getConfig('general/delivery_method'))
        {
            return;
        }
        $tracksCollection = $order->getTracksCollection();
        if(empty($tracksCollection))
        {
            return;
        }
        $tractions = array();
        foreach ($tracksCollection->getItems() as $track)
        {            
            $tractions[] = $track->getTrackNumber();
        }  
        return $tractions;
    }
}
?>