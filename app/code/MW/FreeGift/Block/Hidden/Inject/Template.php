<?php
namespace MW\FreeGift\Block\Hidden\Inject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
class Template extends \Magento\Framework\View\Element\Template
{
    /**
     * @var $_scopeConfig
     */
    protected $_scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';
        return $this->fetchView($this->getTemplateFile());
    }

    function xlog($message = 'null'){
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }
}