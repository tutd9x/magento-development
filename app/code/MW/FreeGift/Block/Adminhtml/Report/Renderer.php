<?php

namespace MW\FreeGift\Block\Adminhtml\Report;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * @category MW
 * @package  MW_FreeGift
 * @module   FreeGift
 * @author   MW Developer
 */
class Renderer extends \Magento\Backend\Block\Template implements RendererInterface
{
    /**
     * Render form element as HTML.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);

        return $this->toHtml();
    }
}
