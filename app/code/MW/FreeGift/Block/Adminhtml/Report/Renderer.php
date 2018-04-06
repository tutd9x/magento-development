<?php

/**
 * MW
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MW.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mage-world.com
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    MW
 * @package     MW_Storelocator
 * @copyright   Copyright (c) 2012 MW (https://www.mage-world.com/)
 * @license     https://www.mage-world.com
 */

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
