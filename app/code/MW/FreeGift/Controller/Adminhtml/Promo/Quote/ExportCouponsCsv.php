<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCouponsCsv extends Quote
{
    /**
     * Export coupon codes as CSV file
     *
     * @return \Magento\Framework\App\ResponseInterface|null
     */
    public function execute()
    {
        $this->_initRule();
        $rule = $this->_coreRegistry->registry('current_promo_sales_rule');
        if ($rule->getId()) {
            $fileName = 'coupon_codes.csv';
            $content = $this->_view->getLayout()->createBlock(
                'MW\FreeGift\Block\Adminhtml\Promo\Quote\Edit\Tab\Coupons\Grid'
            )->getCsvFile();
            return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
        } else {
            $this->_redirect('mw_freegift/*/detail', ['_current' => true]);
            return;
        }
    }
}
