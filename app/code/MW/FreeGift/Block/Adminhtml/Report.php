<?php

/**
 * MW.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MW.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mage-world.com
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    MW
 * @package     MW_Storelocator
 * @copyright   Copyright (c) 2012 MW (https://www.mage-world.com/)
 * @license     https://www.mage-world.com
 */

namespace MW\FreeGift\Block\Adminhtml;

/**
 * @category MW
 * @package  MW_FreeGift
 * @module   FreeGift
 * @author   MW Developer
 */
class Report extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param array                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     */
    protected function _construct()
    {
        parent::_construct();

        $this->buttonList->remove('save');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
    }
}
