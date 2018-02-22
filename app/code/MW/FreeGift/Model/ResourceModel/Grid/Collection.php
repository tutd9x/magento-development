<?php
namespace MW\FreeGift\Model\ResourceModel\Grid;

class Collection extends \MW\FreeGift\Model\ResourceModel\Rule\Collection
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
