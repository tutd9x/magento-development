<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

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
//        $this->addChild('lastOrders', 'Magento\Backend\Block\Dashboard\Orders\Grid');

//        $this->addChild('totals', 'Magento\Backend\Block\Dashboard\Totals');

//        $this->addChild('sales', 'Magento\Backend\Block\Dashboard\Sales');

        $this->addChild('mw_report', 'MW\FreeGift\Block\Adminhtml\Report\Dashboard\Grid');

        $isChartEnabled = $this->_scopeConfig->getValue(
            self::XML_PATH_ENABLE_CHARTS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($isChartEnabled) {
//            $block = $this->getLayout()->createBlock('Magento\Backend\Block\Dashboard\Diagrams');
            $block = $this->getLayout()->createBlock('MW\FreeGift\Block\Adminhtml\Report\Dashboard\Diagrams');
        }else {
            $block = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Template'
            )->setTemplate(
                'dashboard/graph/disabled.phtml'
            )->setConfigUrl(
                $this->getUrl(
                    'adminhtml/system_config/edit',
                    ['section' => 'admin', '_fragment' => 'admin_dashboard-link']
                )
            );
        }
        $this->setChild('diagrams', $block);

//        $this->addChild('grids', 'Magento\Backend\Block\Dashboard\Grids');

        parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getSwitchUrl()
    {
        if ($url = $this->getData('switch_url')) {
            return $url;
        }
        return $this->getUrl('mw_freegift/*/*', ['_current' => true, 'period' => null]);
    }
}