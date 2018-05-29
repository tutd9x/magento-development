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
        $this->addChild('mw_report', 'MW\FreeGift\Block\Adminhtml\Report\Dashboard\Grid');

        $isChartEnabled = $this->_scopeConfig->getValue(
            self::XML_PATH_ENABLE_CHARTS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($isChartEnabled) {
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

        parent::_prepareLayout();
    }

}
