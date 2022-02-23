<?php
namespace WDPH\GenikiTaxydromiki\Model;

class Voucher extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'wdph_genikitaxydromiki_voucher';
	protected $_cacheTag = 'wdph_genikitaxydromiki_voucher';
	protected $_eventPrefix = 'wdph_genikitaxydromiki_voucher';

	protected function _construct()
	{
		$this->_init('WDPH\GenikiTaxydromiki\Model\ResourceModel\Voucher');
	}

    public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

    public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}
?>