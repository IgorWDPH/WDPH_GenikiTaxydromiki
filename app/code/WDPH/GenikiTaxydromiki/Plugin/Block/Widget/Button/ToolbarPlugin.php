<?php
namespace WDPH\GenikiTaxydromiki\Plugin\Block\Widget\Button;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use WDPH\GenikiTaxydromiki\Helper\Data;

class ToolbarPlugin
{
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function beforePushButtons(ToolbarContext $toolbar, AbstractBlock $context, ButtonList $buttonList)
    {        
        $order = false;
        $nameInLayout = $context->getNameInLayout();
        if('sales_order_edit' == $nameInLayout)
        {
            $order = $context->getOrder();
        }
        if(!$order || $order->getShippingMethod() != $this->helper->getConfig('general/delivery_method'))
        {
            return [$context, $buttonList];
        }
        if(!$order->isCanceled() && $order->canShip())
        {
            $url = $context->getUrl('genikitaxydromiki/index/createVoucher');
            $buttonList->add(
                'create_voucher',
                [
                    'label' => __('Create Voucher'),
                    'on_click' => sprintf("location.href = '%s';", $url),
                    'class' => 'primary',
                    'id' => 'create_voucher'
                ]
            );
            $buttonList->remove('order_ship');
        }
        elseif(!$order->isCanceled() && $order->getStatus() == 'complete')
        {
            $url = $context->getUrl('genikitaxydromiki/index/printVoucher');
            $buttonList->add(
                'print_voucher',
                [
                    'label' => __('Print Voucher'),
                    'on_click' => sprintf("location.href = '%s';", $url),
                    'class' => 'primary',
                    'id' => 'print_voucher'
                ]
            );
            //DELETE VOUCHER ACTION
            /*$url = $context->getUrl('genikitaxydromiki/index/deleteVoucher');
            $message = __('Do you really want to delete this voucher?');
            $buttonList->add(
                'delete_voucher',
                [
                    'label' => __('Delete Voucher'),
                    //'on_click' => sprintf("location.href = '%s';", $url),
                    'on_click' => "confirmSetLocation('{$message}', '{$url}')",
                    'class' => 'primary',
                    'id' => 'delete_voucher'
                ]
            );*/
        }

        return [$context, $buttonList];
    }
}
?>