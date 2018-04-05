<?php
/**
 * Copyright 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Used in creating options for Yes|No config value selection
 *
 */
namespace MW\FreeGift\Model\System\Config;

class Showfreegiftpromotion implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Yes, show it on cart')],
            ['value' => 2, 'label' => __('Yes, show it on checkout')],
            ['value' => 3, 'label' => __('Yes, show it on cart and checkout')],
            ['value' => 4, 'label' => __('No, hide it')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
//    public function toArray()
//    {
//        return [0 => __('No'), 1 => __('Yes')];
//    }

}
