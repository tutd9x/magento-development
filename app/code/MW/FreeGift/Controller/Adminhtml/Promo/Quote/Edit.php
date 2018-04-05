<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;
class Edit extends \MW\FreeGift\Controller\Adminhtml\Promo\Quote
{
    /**
     * Promo quote edit action
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('MW\FreeGift\Model\Salesrule');

        if ($id) {
            $model->load($id);
            if (!$model->getRuleId()) {
                $this->messageManager->addError(__('This rule no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        $model->getActions()->setJsFormObject('rule_actions_fieldset');
        $this->_coreRegistry->register('current_promo_quote_rule', $model);
        $this->_initAction();
        $this->_view->getLayout()->getBlock('promo_quote_edit')->setData('action', $this->getUrl('*/*/save'));
        $this->_addBreadcrumb($id ? __('Edit Rule') : __('New Rule'), $id ? __('Edit Rule') : __('New Rule'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getRuleId() ? $model->getName() : __('Free Gift Rule')
        );
        $this->_view->renderLayout();
    }
}
