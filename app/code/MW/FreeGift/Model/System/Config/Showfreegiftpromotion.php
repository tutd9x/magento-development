<?php
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
}
