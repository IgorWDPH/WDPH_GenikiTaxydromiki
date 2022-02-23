<?php
namespace WDPH\GenikiTaxydromiki\Block\Order\Email\Shipment;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Block\Order\Email\Shipment\Items as ShipmentItems;
use WDPH\GenikiTaxydromiki\Helper\Data as GenikiTaxydromikiHelper;
use Magento\Framework\Url as FUrl;

class Items extends ShipmentItems
{
    protected $genikiTaxydromikiHelper;
    protected $fUrl;
    protected $route = 'genikitaxydromiki/shipping/trackparcelbynum';

    public function __construct(
        Context $context,
        array $data = [],
        ?OrderRepositoryInterface $orderRepository = null,
        ?ShipmentRepositoryInterface $creditmemoRepository = null,
        GenikiTaxydromikiHelper $genikiTaxydromikiHelper,
        FUrl $fUrl
    )
    {
        parent::__construct($context, $data, $orderRepository, $creditmemoRepository);
        $this->genikiTaxydromikiHelper = $genikiTaxydromikiHelper;
        $this->fUrl = $fUrl;
    }

    public function getGTTractionUrls()
    {
        $tractions = $this->genikiTaxydromikiHelper->getOrderTractions($this->getOrder());
        if(empty($tractions))
        {
            return;
        }
        $links = array();
        foreach($tractions as $traction)
        {
            $links[$traction] = $this->fUrl->getUrl($this->route, array('pod' => $traction));
        }
        return $links;
    }
}
?>