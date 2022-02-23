<?php
namespace WDPH\GenikiTaxydromiki\Model\ResourceModel\Voucher;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'wdph_genikitaxydromiki_voucher_collection';
	protected $_eventObject = 'voucher_collection';
	
	protected function _construct()
	{
		$this->_init('WDPH\GenikiTaxydromiki\Model\Voucher', 'WDPH\GenikiTaxydromiki\Model\ResourceModel\Voucher');
	}

}
?>