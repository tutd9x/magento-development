<?php
namespace Magento\SalesRule\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCouponsXml extends Quote
{
    /**
     * Export coupon codes as excel xml file
     *
     * @return \Magento\Framework\App\ResponseInterface|null
     */
    public function execute()
    {
        $this->_initRule();
        $rule = $this->_coreRegistry->registry('current_promo_sales_rule');
        if ($rule->getId()) {
            $fileName = 'coupon_codes.xml';
            $content = $this->_view->getLayout()->createBlock(
                'MW\FreeGift\Block\Adminhtml\Promo\Quote\Edit\Tab\Coupons\Grid'
            )->getExcelFile(
                $fileName
            );
            return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
        } else {
            $this->_redirect('mw_freegift/*/detail', ['_current' => true]);
            return;
        }
    }
}
