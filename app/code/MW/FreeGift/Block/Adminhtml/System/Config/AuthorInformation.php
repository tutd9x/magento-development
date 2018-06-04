<?php
namespace MW\FreeGift\Block\Adminhtml\System\Config;

class AuthorInformation extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        $text = [
            '<a target="_blank" href="http://www.mage-world.com/">www.Mage-World.com</a>',
            '<a href="mailto:support@mage-world.com">support@mage-world.com.</a>'
        ];
        $html .= __('The Free Gift Pro Extension is developed and supported by %1. If you need any support or have any question please contact us at %2', $text);
        return $html;
    }
}
