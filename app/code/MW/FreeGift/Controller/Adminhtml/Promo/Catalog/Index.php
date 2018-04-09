<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;
use MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

class Index extends Catalog
{
    /**
     * @return void
     */
    public function execute()
    {
        /** @var \MW\FreeGift\Model\Flag $dirtyRules */
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
