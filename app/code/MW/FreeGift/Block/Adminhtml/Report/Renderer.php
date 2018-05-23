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
    protected $_order;
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param array                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \MW\FreeGift\Model\Config $config,
        array $data = []
    ) {
        $this->_order = $orderFactory;
        $this->config = $config;
        parent::__construct($context, $data);
    }

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

    public function getOrderData(){
        date_default_timezone_set('UTC');

        // Start date
        $date = $this->config->getReportTimeStart();
        // End date
        $end_date = date('m/d/y', time());
        $dateData = null;
        $dataExport = array();

        $startTimeStamp = strtotime($date);
        $endTimeStamp = strtotime($end_date);
        $timeDiff = abs($endTimeStamp - $startTimeStamp);
        $numberDays = $timeDiff/86400;  // 86400 seconds in one day
        $numItems = intval($numberDays);
        $i = 0;
        while (strtotime($date) <= strtotime($end_date)) {

            $date = date ("m/d/y", strtotime("+1 day", strtotime($date)));
            $orderCollection = $this->_order->create()->getCollection();
            $fromDate = date('Y-m-d', strtotime($date)); //"2018-05-22";
            $toDate   = date('Y-m-d', strtotime("+1 day", strtotime($date)));// "2018-04-19";
            $orderFilter = $orderCollection
                ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
                ->addAttributeToFilter('status', array('eq' => "pending"));
            $orderValueInDay = 0;
            $freeGiftValueInDay = 0;
            foreach($orderFilter as $order){
                $orderDateCreate = date('m/d/y', strtotime($order->getCreatedAt()));
                if($orderDateCreate == $date){
                    $orderValue = $order->getBaseGrandTotal(); //\Zend_Debug::dump($orderValue) ;
                    $orderValueInDay += $orderValue;
                    $orderItems = $order->getAllVisibleItems();
                    $freeGiftValue = 0;
                    foreach($orderItems as $item){
                        if($this->checkIsGift($item)){
                            $itemPrice = $item->getBaseOriginalPrice();
                            $freeGiftValue += $itemPrice;
                        }
                    }
                    $freeGiftValueInDay += $freeGiftValue;

                }else{
                    $orderValueInDay = 0;
                    $freeGiftValueInDay = 0;
                }
            }
//            if (++$i === $numItems){
//                $dataExport[] .= '\''.$date.',"'.$orderValueInDay.'","'.$freeGiftValueInDay.'" \'';
                $dataExport[] = array(strtotime($date)*1000,$orderValueInDay);
//            }else{
//                $dataExport[] .= '\''.$date.',"'.$orderValueInDay.'","'.$freeGiftValueInDay.'"\n \' + ';
//                $dataExport[] = array(strtotime($end_date)*1000,$orderValueInDay);
//            }
        }
        $myJSON = json_encode($dataExport);
        return $myJSON;
    }
    public function getFreeGiftData(){
        date_default_timezone_set('UTC');
        // Start date
        $date = $this->config->getReportTimeStart();
        // End date
        $end_date = date('m/d/y', time());
        $dateData = null;
        $dataExport = array();

        $startTimeStamp = strtotime($date);
        $endTimeStamp = strtotime($end_date);
        $timeDiff = abs($endTimeStamp - $startTimeStamp);
        $numberDays = $timeDiff/86400;  // 86400 seconds in one day
        $numItems = intval($numberDays);
        $i = 0;
        while (strtotime($date) <= strtotime($end_date)) {

            $date = date ("m/d/y", strtotime("+1 day", strtotime($date)));
            $orderCollection = $this->_order->create()->getCollection();
            $fromDate = date('Y-m-d', strtotime($date)); //"2018-05-22";
            $toDate   = date('Y-m-d', strtotime("+1 day", strtotime($date)));// "2018-04-19";
            $orderFilter = $orderCollection
                ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
                ->addAttributeToFilter('status', array('eq' => "pending"));
            $orderValueInDay = 0;
            $freeGiftValueInDay = 0;
            foreach($orderFilter as $order){
                $orderDateCreate = date('m/d/y', strtotime($order->getCreatedAt()));
                if($orderDateCreate == $date){
                    $orderValue = $order->getBaseGrandTotal(); //\Zend_Debug::dump($orderValue) ;
                    $orderValueInDay += $orderValue;
                    $orderItems = $order->getAllVisibleItems();
                    $freeGiftValue = 0;
                    foreach($orderItems as $item){
                        if($this->checkIsGift($item)){
                            $itemPrice = $item->getBaseOriginalPrice();
                            $freeGiftValue += $itemPrice;
                        }
                    }
                    $freeGiftValueInDay += $freeGiftValue;

                }else{
                    $orderValueInDay = 0;
                    $freeGiftValueInDay = 0;
                }
            }
            $dataExport[] = array(strtotime($date)*1000, $freeGiftValueInDay);
        }
        $myJSON = json_encode($dataExport);
        return $myJSON;
    }

    public function checkIsGift($item){
        $productOptions = $item->getProductOptions();
        if(isset($productOptions['info_buyRequest']['freegift_parent_key']) && isset($productOptions['info_buyRequest']['freegift_rule_data'])){
            return true;
        }
        if(isset($productOptions['info_buyRequest']['free_sales_key']) && isset($productOptions['info_buyRequest']['freegift_rule_data'])){
            return true;
        }
        return false;
    }
}
