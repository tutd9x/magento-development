<?php
/**
 * Sales Rules resource collection model
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Model\ResourceModel\SalesRule\Quote;

class Collection extends \MW\FreeGift\Model\ResourceModel\SalesRule\Collection
{
    /**
     * Add websites for load
     *
     * @return $this
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->addWebsitesToResult();
        return $this;
    }
}
