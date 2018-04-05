<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class Chooser extends \MW\FreeGift\Controller\Adminhtml\Promo\Quote
{
    /**
     * Chooser source action
     *
     * @return void
     */
    public function execute()
    {
        $uniqId = $this->getRequest()->getParam('uniq_id');
        $chooserBlock = $this->_view->getLayout()->createBlock(
            'MW\FreeGift\Block\Adminhtml\Promo\Widget\Chooser',
            '',
            ['data' => ['id' => $uniqId]]
        );
        $this->getResponse()->setBody($chooserBlock->toHtml());
    }
}
