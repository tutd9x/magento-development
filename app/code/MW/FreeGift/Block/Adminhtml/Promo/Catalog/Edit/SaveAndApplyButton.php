<?php

namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\GenericButton;

class SaveAndApplyButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->canRender('save_apply')) {
            $data = [
                'label' => __('Save and Apply'),
                'class' => 'save',
                'on_click' => '',
                'sort_order' => 80,
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event' => 'click',
                            'target' => '#save',
                            'eventData' => ['action' => ['args' => ['auto_apply' => 1]]],
                        ],
                    ],
                ]
            ];
        }
        return $data;
    }
}
