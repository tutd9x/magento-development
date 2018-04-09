<?php
namespace MW\FreeGift\Model\ResourceModel\Grid;

use MW\FreeGift\Model\ResourceModel\Rule\Collection;

class GridRuleCollection extends Collection
{
    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addWebsitesToResult();
        return $this;
    }
}
