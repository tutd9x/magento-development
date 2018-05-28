<?php
namespace MW\FreeGift\Controller\Adminhtml\Report\Dashboard;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Json\Helper\Data;

class Index extends \MW\FreeGift\Controller\Adminhtml\Promo\Dashboard
//class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Data $jsonHelper
     */
    public function __construct(
        Context $context,
        Data $jsonHelper
    ) {
        parent::__construct($context);
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Apply all active catalog price rules
     *
     * @return $this
     */
    public function execute()
    {
        $data = array();
        $data['report']=null;
//        $data['report']['order']=null;
        $data['report']['redeemed']=array([2018, 4, 25, 1], [2018, 4, 26, 4], [2018, 4, 27, 2], [2018, 4, 28, 6], [2018, 4, 29, 5]);
        $data['report']['rewarded']=array([2018, 4, 25, 4], [2018, 4, 26, 6], [2018, 4, 27, 2], [2018, 4, 28, 3], [2018, 4, 29, 9]);
        $data['title']="FreeGift / Report FreeGift";
        $data['report_activities']="";
        $data['statistics']=array(
            'total_order'=> "$0.00",
            'total_gift' => "$0.00",
            'number_customer' => "$0.00",
            'avg_gift_per_customer' => "$0.00"
        );

        /** @var \Magento\Framework\Controller\Result\Json $result */
//        print json_encode($data);
        return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($data));
    }

}