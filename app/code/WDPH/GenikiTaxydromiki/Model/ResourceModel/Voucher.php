<?php
namespace WDPH\GenikiTaxydromiki\Model\ResourceModel;

class Voucher extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{	
	public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('wdph_genikitaxydromiki_voucher', 'entity_id');
	}	
}
?>