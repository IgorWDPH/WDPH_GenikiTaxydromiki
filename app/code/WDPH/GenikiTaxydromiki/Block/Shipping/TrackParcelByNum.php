<?php
namespace WDPH\GenikiTaxydromiki\Block\Shipping;

use Magento\Framework\View\Element\Template\Context;
use WDPH\GenikiTaxydromiki\Model\TractionInfo;

class TrackParcelByNum extends \Magento\Framework\View\Element\Template
{
	protected $trackingInfo;

	public function __construct(
		Context $context,
		TractionInfo $tractionData
	)
	{
		parent::__construct($context);
		$this->trackingInfo = $tractionData;
	}

	public function getTrackingData()
	{
		return $this->trackingInfo->getTractionData($this->getData('POD'));
	}
}
?>