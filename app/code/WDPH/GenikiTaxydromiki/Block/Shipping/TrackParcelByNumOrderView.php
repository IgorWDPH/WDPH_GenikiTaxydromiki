<?php
namespace WDPH\GenikiTaxydromiki\Block\Shipping;

use Magento\Sales\Block\Order\View as OrderView;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use WDPH\GenikiTaxydromiki\Helper\Data as GenikiTaxydromikiHelper;
use WDPH\GenikiTaxydromiki\Model\TractionInfo;

class TrackParcelByNumOrderView extends OrderView
{
    protected $genikiTaxydromikiHelper;
    protected $trackingInfo;

    public function __construct(
        Context $context,
        Registry $registry,
        HttpContext $httpContext,
        PaymentHelper $paymentHelper,
        GenikiTaxydromikiHelper $genikiTaxydromikiHelper,
        TractionInfo $tractionData,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $httpContext, $paymentHelper, $data);
        $this->genikiTaxydromikiHelper = $genikiTaxydromikiHelper;
        $this->trackingInfo = $tractionData;
    }

    public function getTrackingData()
    {
        $tractions = $this->genikiTaxydromikiHelper->getOrderTractions($this->getOrder());        
        $tractionData = array();
        if(empty($tractions))
        {
            return $tractionData;
        }
        foreach ($tractions as $traction)
        {
            $tractionData[] = $this->trackingInfo->getTractionData($traction);
        }        
        return $tractionData;
    }
}
?>