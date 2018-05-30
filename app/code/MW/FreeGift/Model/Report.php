<?php
namespace MW\FreeGift\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Report extends \Magento\Framework\Model\AbstractModel
{
    const REPORT_RAGE_LAST_24H = 1;
    const REPORT_RAGE_LAST_WEEK = 2; // Last week;
    const REPORT_RAGE_LAST_MONTH = 3; // Last month
    const REPORT_RAGE_LAST_7DAYS = 4; // Last month
    const REPORT_RAGE_LAST_30DAYS = 5;// Last 6 months
    const REPORT_RAGE_CURRENT_YEAR = 6; // Current year
    const REPORT_RAGE_CUSTOM = 7; //user custom time value

    const PENDING = 'PENDING'; //haven't change points yet
    const COMPLETE   = 'COMPLETE';
    const UNCOMPLETE = 'UNCOMPLETE';
    const REFUNDED = 'REFUNDED'; // refunded

    protected $all_months = 0;
    protected $use_type = [1, 2, 3, 4, 5, 6, 14, 8, 30, 15, 16, 12, 18, 21, 32, 25, 29, 26, 27, 19, 50, 51, 52, 53];
    protected $group_signup = [1];
    protected $group_review = [2];
    protected $group_order = [3, 8, 30];
    protected $group_birthday = [26];
    protected $group_newsletter = [16];
    protected $group_tag = [];
    protected $group_social = [];
    protected $group_referal = [4, 5, 6, 14];
    protected $group_other = [25, 29, 15, 12, 18, 21, 32, 27, 19, 50, 51, 53, 52];

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $_objectManager;

    /**
     * @var $customerFactory
     */
    protected $customerFactory;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $_orderItemFactory;

    protected $productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateFormat;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    protected $helperFreeGift;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;
    /**
     * @var TimezoneInterface
     */
    private $timezone;
    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Authorization\Model\ResourceModel\Rules $resource
     * @param \Magento\Authorization\Model\ResourceModel\Permissions\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Authorization\Model\ResourceModel\Rules $resource,
        \Magento\Authorization\Model\ResourceModel\Permissions\Collection $resourceCollection,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime $dateFormat,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->customerFactory = $customerFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderItemFactory = $orderItemFactory;
        $this->productRepository = $productRepository;
        $this->dateFormat = $dateFormat;
        $this->dateTime = $dateTime;
        $this->helperFreeGift = $helperFreeGift;
        $this->timezone = $timezone;
        $this->pricingHelper = $pricingHelper;
    }

    public function prepareCollection($data)
    {
        if ($data['report_range'] == self::REPORT_RAGE_CUSTOM) {
            if ($this->_validationDate($data) == false) {
                return $this;
            }
            /** Get all month between two dates */
            $this->all_months = $this->_getMonths($data['from'], $data['to']);
        }
        $users = [];
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
        $collection = $this->customerFactory->create()->getCollection();
        foreach ($collection->getData() as $user) {
            $users[] = $user['entity_id'];
        }

        /* FreeGift -- Query to get total order value */
        $collection = $this->_orderFactory->create()->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', self::COMPLETE);
        $collection->addExpressionFieldToSelect('total_order_sum', 'sum(main_table.total_invoiced)', 'total_order_sum');
        $this->_buildCollection($collection, $data);
        $collection_order = $collection;

        $total_order = 0;
        $temp = [];
        foreach ($collection_order as $co_order) {
            $total_order = $total_order + $co_order->getTotalOrderSum();
        }

        /**FreeGift - Query to get gift collection */
        $collection_temp_order = $this->_orderFactory->create()->getCollection();
        $collection_temp_order->removeAllFieldsFromSelect();
        $collection_temp_order->addFieldToFilter('status', self::COMPLETE);
        $this->_buildCollection($collection_temp_order, $data);

        $collection_gift = $collection_temp_order->getData();

        $total_order_number = 0;

        $i = 0;
        $number_product_gift = 0;
        $number_customer_array = [];

        $temp = [];
        /* foreach collection for gift */
        foreach ($collection_temp_order as $order) {
            $order_id_string = $order->getGroupId();
            $order_id_array = explode(",", $order_id_string);

            $total_gift_price = 0;

            foreach ($order_id_array as $order_id) {
                $total_order_number++;
                $collection_order_temp = $this->_orderFactory->create()->load($order_id);
                $customer_id =  $collection_order_temp->getCustomerId();
                array_push($number_customer_array, $customer_id);

                $collection_order_item = $this->_orderItemFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('order_id', $order_id);

                foreach ($collection_order_item as $product_gift) {
                    if ($this->checkIsGift($product_gift)) {
                        $number_gift = $product_gift->getQtyShipped();
                        $total_gift_price = $total_gift_price + $number_gift * $product_gift->getBaseOriginalPrice();
                    }
                }
            }

            $number_product_gift = $total_gift_price;
            $collection_gift[$i]['total_gift_sum'] = $total_gift_price;
            $i++;
        }
        $number_customer = 0;
        $flag = true;
        for ($i=0; $i<count($number_customer_array); $i++) {
            $flag = true;
            for ($j=0; $j<$i; $j++) {
                if ($number_customer_array[$i] == $number_customer_array[$j]) {
                    $flag = false;
                    break;
                }
            }
            if ($flag==true) {
                $number_customer++;
            }
        }

        switch ($data['report_range']) {
            case self::REPORT_RAGE_LAST_24H:
                $_time = $this->getPreviousDateTime(24);
                $start_24h_time = $this->dateFormat->formatDate(date('Y-m-d h:i:s', $_time), 'medium', true);
                $start_24h_time = strtotime($start_24h_time);
                $start_time = [
                    'h'   => (int)date('H', $start_24h_time),
                    'd'   => (int)date('d', $start_24h_time),
                    'm'   => (int)date('m', $start_24h_time),
                    'y'   => (int)date('Y', $start_24h_time),
                ];
                $rangeDate = $this->_buildArrayDate(self::REPORT_RAGE_LAST_24H, $start_time['h'], $start_time['h'] + 24, $start_time);
                // return redeemed and rewarded value
                $_data = $this->_buildResult($collection_gift, $collection_order, 'hour', $rangeDate);
                // return date_start value
                $_data['report']['date_start'] = $start_time;
                break;
            case self::REPORT_RAGE_LAST_WEEK:
                $start_time = strtotime("-6 day", strtotime("Sunday Last Week"));
                $startDay = date('d', $start_time);
                $endDay = date('d', strtotime("Sunday Last Week"));
                $rangeDate = $this->_buildArrayDate(self::REPORT_RAGE_LAST_WEEK, $startDay, $endDay);
                $_data = $this->_buildResult($collection_gift, $collection_order, 'day', $rangeDate);

                $_data['report']['date_start'] = [
                    'd'   => (int)date('d', $start_time),
                    'm'   => (int)date('m', $start_time),
                    'y'   => (int)date('Y', $start_time),
                ];

                break;
            case self::REPORT_RAGE_LAST_MONTH:
                $last_month_time = strtotime($this->_getLastMonthTime());
                $last_month = date('m', $last_month_time);
                $start_day = 1;
                $end_day = $this->dayInMonth($last_month, null);
                $rangeDate = $this->_buildArrayDate(self::REPORT_RAGE_LAST_MONTH, $start_day, $end_day);

                $_data = $this->_buildResult($collection_gift, $collection_order, 'day', $rangeDate);
                $_data['report']['date_start'] = [
                    'd'   => $start_day,
                    'm'   => (int)$last_month,
                    'y'   => (int)date('Y', $last_month_time),
                    'total_day' => $end_day
                ];

                break;
            case self::REPORT_RAGE_LAST_7DAYS:
            case self::REPORT_RAGE_LAST_30DAYS:
                $last_x_day = 7;
                if ($data['report_range'] == self::REPORT_RAGE_LAST_7DAYS) {
                    $last_x_day = 7;
                } elseif ($data['report_range'] == self::REPORT_RAGE_LAST_30DAYS) {
                    $last_x_day = 30;
                }

                $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', $this->timezone->scopeTimeStamp()));
                $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));

                $original_time = [
                    'from'  => $start_day,
                    'to'    => $end_day
                ];
                $rangeDate = $this->_buildArrayDate(self::REPORT_RAGE_CUSTOM, 0, 0, $original_time);
                $_data = $this->_buildResult($collection_gift, $collection_order, 'multiday', $rangeDate, $original_time);
                break;
            case self::REPORT_RAGE_CUSTOM:
                $original_time = [
                    'from'  => date('m/d/Y h:i:s', strtotime($data['from'])),
                    'to'    => date('m/d/Y h:i:s', strtotime($data['to']))
                ];
                $rangeDate = $this->_buildArrayDate(self::REPORT_RAGE_CUSTOM, 0, 0, $original_time);
                $_data = $this->_buildResult($collection_gift, $collection_order, 'multiday', $rangeDate, $original_time);
                break;
        }

        $_data['title'] = __('FreeGift / Report FreeGift');

        $_data['report_activities'] = '';

        $_data['statistics']['total_order'] = $this->pricingHelper->currency($total_order, true, false);
        $_data['statistics']['total_gift'] = $this->pricingHelper->currency($number_product_gift, true, false);
        $_data['statistics']['number_customer'] = $number_customer;
        $avg_gift_per_customer = $number_customer ? $number_product_gift/$number_customer : 0;
        $_data['statistics']['avg_gift_per_customer'] = $this->pricingHelper->currency(round($avg_gift_per_customer, 2), true, false);
        $avg_gift_per_order = $total_order_number ? $number_product_gift/$total_order_number : 0;
        $_data['statistics']['avg_gift_per_order'] = $this->pricingHelper->currency(round($avg_gift_per_order, 2), true, false);

        return json_encode($_data);
    }

    protected function _buildResult($collection_gift, $collection_order, $type, $rangeDate, $original_time = null)
    {
        $_data = [];

        try {
            if ($type == 'multiday') {
                foreach ($rangeDate as $year => $months) {
                    foreach ($months as $month => $days) {
                        foreach ($days as $day) {
                            $_data['report']['redeemed'][$year."-".$month."-".$day]  = [$year, $month, $day, 0];
                        }
                        foreach ($days as $day) {
                            $_data['report']['rewarded'][$year."-".$month."-".$day]  = [$year, $month, $day, 0];
                        }

                        foreach ($collection_gift as $redeemd) {
                            if ($redeemd['month'] == $month) {
                                foreach ($days as $day) {
                                    if ($redeemd['day'] == $day) {
                                        $_data['report']['redeemed'][$year."-".$month."-".$day]  = [$year, $month, $day, (int)$redeemd['total_gift_sum']];
                                    }
                                }
                            }
                        }

                        foreach ($collection_order as $reward) {
                            if ($reward->getMonth() == $month) {
                                foreach ($days as $day) {
                                    if ($reward->getDay() == $day) {
                                        $_data['report']['rewarded'][$year."-".$month."-".$day]  = [$year, $month, $day, (int)$reward->getTotalOrderSum()];
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $i = 0;
                switch ($type) {
                    case 'hour':
                        $rangeTempDate = reset($rangeDate);
                        $i = $rangeTempDate['incr_hour'];
                        break;
                    case 'day':
                        $rangeTempDate = reset($rangeDate);
//                        $i = $rangeTempDate['count_day'];
                        $i = $rangeTempDate['native_day']; //leric edit
                        break;
                }
                foreach ($rangeDate as $date) {
                    $count = 0;

                    switch ($type) {
                        case 'hour':
                            $count = $date['native_hour'];
                            break;
                        case 'day':
                            $count = $date['native_day'];
                            break;
                    }

                    $_data['report']['redeemed'][$i] = 0;
                    $_data['report']['rewarded'][$i] = 0;
                    $_data['report']['order'][$i] = 0;
                    foreach ($collection_gift as $redeemd) {
                        if ((int)$redeemd[$type] == (int)$count) {
                            if (isset($date['day']) && $date['day'] == (int)$redeemd['day']) {
                                //$_data['report']['redeemed'][$i] = (int)$redeemd['total_gift_sum'];
                                $_data['report']['redeemed'][$i] = $this->pricingHelper->currency((int)$redeemd['total_gift_sum'], false, false);
                            } elseif (!isset($date['day'])) {
                                //$_data['report']['redeemed'][$i] = (int)$redeemd['total_gift_sum'];
                                $_data['report']['redeemed'][$i] = $this->pricingHelper->currency((int)$redeemd['total_gift_sum'], false, false);
                            }
                        }
                    }
                    foreach ($collection_order as $reward) {
                        if ((int)$reward->{"get$type"}() == (int)$count) {
                            if (isset($date['day']) && $date['day'] == (int)$reward->getDay()) {
                                //$_data['report']['rewarded'][$i] = (int)$reward->getTotalOrderSum() ;
                                $_data['report']['rewarded'][$i] = $this->pricingHelper->currency((int)$reward->getTotalOrderSum(), false, false);
                            } elseif (!isset($date['day'])) {
                                //$_data['report']['rewarded'][$i] = (int)$reward->getTotalOrderSum() ;
                                $_data['report']['rewarded'][$i] = $this->pricingHelper->currency((int)$reward->getTotalOrderSum(), false, false);
                            }
                        }
                    }

//                    foreach($collection_order as $order)
//                    {
//                        if((int)$order->{"get$type"}() == $count)
//                        {
//                            $_data['report']['order'][$i] = 0;
//                        }
//                    }
                    $i++;
                }
            }
            if (isset($_data['report']['redeemed'])) {
                $_data['report']['redeemed'] = array_values($_data['report']['redeemed']);
            }
            if (isset($_data['report']['rewarded'])) {
                $_data['report']['rewarded'] = array_values($_data['report']['rewarded']);
            }
            if (isset($_data['report']['order'])) {
                $_data['report']['order'] = array_values($_data['report']['order']);
            }
        } catch (\Exception $e) {
        }
        return $_data;
    }
    protected function _buildCollection($collection, $data, $group = true)
    {
        switch ($data['report_range']) {
            case self::REPORT_RAGE_LAST_24H:
                /* Last 24h */
                $_hour = date('Y-m-d H:i:s', strtotime('-1 day', $this->timezone->scopeTimeStamp()));
//                $_hour = date('Y-m-d H:i:s', strtotime('-1 day', $this->dateTime->gmtTimestamp()));
                $start_hour = $this->dateFormat->formatDate($_hour, 'medium', true);
                $_hour = date('Y-m-d H:i:s', strtotime("now"));
                $end_hour = $this->dateFormat->formatDate($_hour, 'medium', true);
                if ($group == true) {
                    $collection->addExpressionFieldToSelect('hour', 'HOUR(CONVERT_TZ(updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\'))', 'hour');
                    $collection->addExpressionFieldToSelect('day', 'DAY(CONVERT_TZ(updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\'))', 'day');
                    $collection->addExpressionFieldToSelect('group_id', 'GROUP_CONCAT(entity_id)', 'group_id');
                    $collection->getSelect()->group(['hour']);
                }
                $where = 'CONVERT_TZ(main_table.updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                $collection->getSelect()->where($where . ' >= "'.$start_hour.'" AND '. $where . ' <= "'.$end_hour .'"');
                break;
            case self::REPORT_RAGE_LAST_WEEK:
                /* Last week */
                $start_day = date('Y-m-d', strtotime("-7 day", strtotime("Sunday Last Week")));
                $end_day = date('Y-m-d', strtotime("Sunday Last Week"));

                if ($group == true) {
                    $collection->addExpressionFieldToSelect('day', 'DAY(updated_at)', 'day');
                    $collection->addExpressionFieldToSelect('group_id', 'GROUP_CONCAT(entity_id)', 'group_id');
                    $collection->getSelect()->group(['day']);
                }
                $where = 'CONVERT_TZ(main_table.updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                $collection->getSelect()->where($where . ' >= "'.$start_day.'" AND '. $where . ' <= "'.$end_day .'"');
                break;
            case self::REPORT_RAGE_LAST_MONTH:
                /* Last month */
                $last_month_time = $this->_getLastMonthTime();
                $last_month = date('m', strtotime($last_month_time));
                $start_day = date('Y', strtotime($last_month_time))."-".$last_month."-1";
                $end_day = date('Y', strtotime($last_month_time))."-".$last_month."-".$this->dayInMonth($last_month, null);

                /** Fix bug next one day */
                $end_day = strtotime($end_day.' +1 day');
                $end_day = date('Y', $end_day)."-".date('m', $end_day)."-".date('d', $end_day);

                if ($group == true) {
                    $collection->addExpressionFieldToSelect('day', 'DAY(updated_at)', 'day');
                    $collection->addExpressionFieldToSelect('group_id', 'GROUP_CONCAT(entity_id)', 'group_id');
                    $collection->getSelect()->group(['day']);
                }
                $where = 'CONVERT_TZ(main_table.updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                $collection->getSelect()->where($where . ' >= "'.$start_day.'" AND '. $where . ' <= "'.$end_day .'"');
                break;
            case self::REPORT_RAGE_LAST_7DAYS:
            case self::REPORT_RAGE_LAST_30DAYS:
                /** Last X days */
                $last_x_day = 7;

                if ($data['report_range'] == self::REPORT_RAGE_LAST_7DAYS) {
                    $last_x_day = 7;
                } elseif ($data['report_range'] == self::REPORT_RAGE_LAST_30DAYS) {
                    $last_x_day = 30;
                }

                $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', $this->timezone->scopeTimeStamp()));
                $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));

                if ($group == true) {
                    $collection->addExpressionFieldToSelect('group_id', 'GROUP_CONCAT(entity_id)', 'group_id');
                    $collection->getSelect()->group(['day']);
                }

                $collection->addExpressionFieldToSelect('month', 'MONTH(updated_at)', 'month');
                $collection->addExpressionFieldToSelect('day', 'DAY(updated_at)', 'day');
                $collection->addExpressionFieldToSelect('year', 'YEAR(updated_at)', 'year');
                $where = 'CONVERT_TZ(main_table.updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                $collection->getSelect()->where($where . ' >= "'.$start_day.'" AND '. $where . ' <= "'.$end_day .'"');
                break;
            case self::REPORT_RAGE_CUSTOM:
                /* Custom range */
                $data['from'] = date('Y-m-d h:i:s', strtotime($data['from']));
                $data['to'] = date('Y-m-d h:i:s', strtotime($data['to']));
                if ($group == true) {
                    $collection->addExpressionFieldToSelect('group_id', 'GROUP_CONCAT(entity_id)', 'group_id');
                    $collection->getSelect()->group(['day']);
                    $collection->addExpressionFieldToSelect('month', 'MONTH(updated_at)', 'month');
                    $collection->addExpressionFieldToSelect('day', 'DAY(updated_at)', 'day');
                    $collection->addExpressionFieldToSelect('year', 'YEAR(updated_at)', 'year');
                    $collection->addExpressionFieldToSelect('group_id', 'GROUP_CONCAT(entity_id)', 'group_id');
                    $collection->getSelect()->group(['day']);
                }
                $where = 'CONVERT_TZ(main_table.updated_at, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                $collection->getSelect()->where($where . ' >= "'.$data['from'].'" AND '. $where . ' <= "'.$data['to'] .'"');
                break;
        }
    }
    protected function _getLastMonthTime()
    {
        return  date('Y-m-d', strtotime("-1 month"));
    }
    protected function _buildArrayDate($type, $from = 0, $to = 23, $original_time = null)
    {
        switch ($type) {
            case self::REPORT_RAGE_LAST_24H:
                $start_day = $original_time['d'];
                for ($i = $from; $i <= $to; $i++) {
                    $data[$i]['incr_hour'] = $i;
                    $data[$i]['native_hour'] = ($i > 24) ? $i - 24 : $i;
                    $data[$i]['day'] = $start_day;

                    if ($i == 23) {
                        $start_day++;
                    }
                }
                break;
            case self::REPORT_RAGE_LAST_WEEK:
                $data = [];
                $day_in_month = $this->dayInMonth(date('m'), date('Y'));
                $clone_from = $from;
                $reset = false;
                for ($i = 1; $i <=7; $i++) {
                    if ($from > $day_in_month && !$reset) {
                        $clone_from = 1;
                        $reset = true;
                    }
                    $data[$i]['count_day'] = $from;
                    $data[$i]['native_day'] = $clone_from;
                    $from++;
                    $clone_from++;
                }

                break;
            case self::REPORT_RAGE_LAST_MONTH:
                for ($i = (int)$from; $i <= $to; $i++) {
                    $data[$i]['native_day'] = (int)$i;
                }
                break;
            case self::REPORT_RAGE_CUSTOM:
                $total_days = $this->_dateDiff($original_time['from'], $original_time['to']);
                if ($total_days > 365) {
                } else {
                    $all_months = $this->_getMonths($original_time['from'], $original_time['to']);
                    $start_time = strtotime($original_time['from']);
                    $start_day  = (int)date('d', $start_time);
                    $count      = 0;
                    $data       = [];

                    $end_day_time = strtotime($original_time['to']);

                    $end_day = [
                        'm' => (int)date('m', $end_day_time),
                        'd' => (int)date('d', $end_day_time),
                        'y' => (int)date('Y', $end_day_time)
                    ];

                    foreach ($all_months as $month) {
                        $day_in_month = $this->dayInMonth($month['m'], $month['y']);
                        for ($day = ($count == 0 ? $start_day : 1); $day <= $day_in_month; $day++) {
                            if ($day > $end_day['d'] && $month['m'] == $end_day['m'] && $month['y'] == $end_day['y']) {
                                continue;
                            }
                            $data[$month['y']][$month['m']][$day] = $day;
                        }
                            $count++;
                    }
                }
                break;
        }
        return $data;
    }
    protected function dayInMonth($month, $year)
    {
        $year = (!$year) ? date('Y', $this->timezone->scopeTimeStamp()) : $year;
        return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
    }
    protected function _dateDiff($d1, $d2)
    {
        // Return the number of days between the two dates:
        return round(abs(strtotime($d1) - strtotime($d2))/86400);
    }
    protected function _validationDate($data)
    {
        if (strtotime($data['from']) > strtotime($data['to'])) {
            return false;
        }
        return true;
    }
    protected function _getMonths($start, $end)
    {
        $start = $start=='' ? time() : strtotime($start);
        $end = $end=='' ? time() : strtotime($end);
        $months = [];
        $data = [];

        for ($i = $start; $i <= $end; $i = $this->getNextMonth($i)) {
            $data['m'] = (int)date('m', $i);
            $data['y'] = (int)date('Y', $i);
            array_push($months, $data);
        }

        return $months;
    }
    protected function getNextMonth($tstamp)
    {
        return (strtotime('+1 months', strtotime(date('Y-m-01', $tstamp))));
    }
    protected function getPreviousDateTime($hour)
    {
        return $this->timezone->scopeTimeStamp() - (3600 * $hour);
    }
    protected function convertNumberToMOnth($num)
    {
        $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        return $months[$num];
    }

    protected function _calOffsetHourGMT()
    {
        return $this->dateTime->calculateOffset($this->helperFreeGift->getStoreConfig('general/locale/timezone'))/60/60;
    }

    public function checkIsGift($item)
    {
        $productOptions = $item->getProductOptions();
        if (isset($productOptions['info_buyRequest']['freegift_parent_key']) && isset($productOptions['info_buyRequest']['freegift_rule_data'])) {
            return true;
        }
        if (isset($productOptions['info_buyRequest']['free_sales_key']) && isset($productOptions['info_buyRequest']['freegift_rule_data'])) {
            return true;
        }
        return false;
    }
}
