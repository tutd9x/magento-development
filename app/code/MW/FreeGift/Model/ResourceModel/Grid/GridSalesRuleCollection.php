<?php
namespace MW\FreeGift\Model\ResourceModel\Grid;

use MW\FreeGift\Model\ResourceModel\SalesRule\Collection;

class GridSalesRuleCollection extends Collection
{
    /**
     * @return $this
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->addWebsitesToResult();
        return $this;
    }
}
