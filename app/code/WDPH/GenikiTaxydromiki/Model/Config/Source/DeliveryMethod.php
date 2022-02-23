<?php
namespace WDPH\GenikiTaxydromiki\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config;

class DeliveryMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    protected $scopeConfig; 
    protected $shipconfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $shipconfig
    )
	{
        $this->shipconfig = $shipconfig;
        $this->scopeConfig = $scopeConfig;
    }    

    public function toOptionArray()
    {
        return $this->getShippingMethods();
    }

    public function getShippingMethods()
    {
        $activeCarriers = $this->shipconfig->getActiveCarriers();
        foreach($activeCarriers as $carrierCode => $carrierModel)
        {
            $options = array();
            if($carrierMethods = $carrierModel->getAllowedMethods())
            {
                foreach ($carrierMethods as $methodCode => $method)
                {
                    $code = $carrierCode . '_' . $methodCode;
                    $options[] = array('value' => $code, 'label' => $method);
                }
                $carrierTitle = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/title');
            }
            $methods[] = array('value' => $options, 'label' => $carrierTitle);
        }
        return $methods;
    }
}
?>