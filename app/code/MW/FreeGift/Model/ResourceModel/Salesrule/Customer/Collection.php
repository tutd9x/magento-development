<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Model\ResourceModel\Salesrule\Customer;

/**
 * SalesRule Model Resource Rule Customer_Collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Collection constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MW\FreeGift\Model\Salesrule\Customer', 'MW\FreeGift\Model\ResourceModel\Salesrule\Customer');
    }
}
