<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class Index extends \MW\FreeGift\Controller\Adminhtml\Promo\Catalog
{
    /**
     * @return void
     */
    public function execute()
    {
        $dirtyRules = $this->_objectManager->create('MW\FreeGift\Model\Flag')->loadSelf();
        if (!empty($dirtyRules)) {
            if ($dirtyRules->getState()) {
                $this->messageManager->addNotice($this->getDirtyRulesNoticeMessage());
            }
        }

        $this->_initAction()->_addBreadcrumb(__('Catalog'), __('Catalog'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Catalog Price Rule'));
        $this->_view->renderLayout();
    }
}
