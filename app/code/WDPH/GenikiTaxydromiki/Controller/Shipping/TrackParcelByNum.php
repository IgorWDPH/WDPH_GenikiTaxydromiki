<?php
namespace WDPH\GenikiTaxydromiki\Controller\Shipping;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class TrackParcelByNum extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		Context $context,
		PageFactory $pageFactory		
	)
	{		
		$this->_pageFactory = $pageFactory;		
		parent::__construct($context);	
	}

	//1506040502
	public function execute()
	{		
		$page = $this->_pageFactory->create();
		$block = $page->getLayout()->getBlock('genikitaxydromiki.trackparcelbynum');
		$block->setData('POD', $this->getRequest()->getParam('pod'));
		return $page;
	}
}
?>