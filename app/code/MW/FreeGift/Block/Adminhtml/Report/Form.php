<?php

/**
 * MW
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

namespace MW\FreeGift\Block\Adminhtml\Report;

/**
 * @category MW
 * @package  MW_FreeGift
 * @module   FreeGift
 * @author   MW Developer
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Prepare form before rendering HTML.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('guide_');

        /*
         * guide for google API
         */
        $fieldset = $form->addFieldset(
            'google_fieldset',
            [
                'legend' => __('Report'),
                'class' => 'guide-fieldset',
            ]
        );

        $fieldset->addField(
            'google',
            'text',
            [
                'name' => 'google',
                'label' => __('report free gift'),
                'title' => __('reportfree gift'),
            ]
        )->setRenderer($this->getChildBlock('freegift_report_render'));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
