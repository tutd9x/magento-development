<?php
namespace MW\FreeGift\Block\Adminhtml\Report;

class Dashboard extends \Magento\Backend\Block\Template
{
    /**
     * Location of the "Enable Chart" config param
     */
    const XML_PATH_ENABLE_CHARTS = 'admin/dashboard/enable_charts';

    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::dashboard/index.phtml';

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->addChild('mw_report', 'MW\FreeGift\Block\Adminhtml\Report\Dashboard\Grid');
        parent::_prepareLayout();
    }

}
