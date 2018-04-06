<?php

namespace MW\FreeGift\Block\Adminhtml\Report;

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
