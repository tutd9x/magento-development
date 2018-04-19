<?php
// @codingStandardsIgnoreFile

namespace MW\FreeGift\Block\Adminhtml\Promo\Quote\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Ui\Component\Layout\Tabs\TabInterface;


/**
 * Cart Price Rule General Information Tab
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Social extends Generic implements TabInterface
{
    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Social Sharing');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Social Sharing');
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Tab class getter
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @return Form
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_promo_sales_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->addTabToForm($model);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param \MW\FreeGift\Api\Data\RuleInterface $model
     * @param string $fieldsetId
     * @param string $formName
     * @return \Magento\Framework\Data\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addTabToForm($model, $fieldsetId = 'social_fieldset', $formName = 'mw_freegift_sales_rule_form')
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset($fieldsetId, ['legend' => __('Social information')]);

//        $stopRulesProcessing = 0;
//        if(isset($model) && $model->getStopRulesProcessing()){
//            $stopRulesProcessing = $model->getStopRulesProcessing();
//        }
//
//        if(isset($model) && $model->getNumberOfFreeGift()){
//            $numberOfFreeGift = $model->getNumberOfFreeGift();
//        }else{
//            $numberOfFreeGift = 1;
//        }

        $fieldset->addField(
            'enable_social',
            'select',
            [
                'label' => __('Social Sharing'),
                'title' => __('Social Sharing'),
                'name' => 'enable_social',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'data-form-part' => $formName
            ]
        );

        $fieldset->addField(
            'google_plus',
            'select',
            [
                'label' => __('Google Plus'),
                'title' => __('Google Plus'),
                'name' => 'google_plus',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'data-form-part' => $formName
            ]
        );

        $fieldset->addField(
            'like_fb',
            'select',
            [
                'label' => __('Facebook Like'),
                'title' => __('Facebook Like'),
                'name' => 'like_fb',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'data-form-part' => $formName
            ]
        );

        $fieldset->addField(
            'share_fb',
            'select',
            [
                'label' => __('Facebook Share'),
                'title' => __('Facebook Share'),
                'name' => 'share_fb',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'data-form-part' => $formName
            ]
        );

        $fieldset->addField(
            'twitter',
            'select',
            [
                'label' => __('Twitter Tweet'),
                'title' => __('Twitter Tweet'),
                'name' => 'twitter',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'data-form-part' => $formName
            ]
        );

        $fieldset->addField(
            'default_message',
            'textarea',
            [
                'name' => 'default_message',
                'label' => __('Default Message'),
                'title' => __('Default Message'),
                'style' => 'height: 100px;',
                'data-form-part' => $formName
            ]
        );

        if(isset($model)){
            $form->setValues($model->getData());
        }
        return $form;
    }
}
