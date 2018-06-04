<?php
namespace MW\FreeGift\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'catalogrule'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_rule'))) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mw_freegift_rule'))
                ->addColumn(
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Rule Id'
                )
                ->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Name'
                )
                ->addColumn(
                    'description',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    [],
                    'Description'
                )
                ->addColumn(
                    'from_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    [],
                    'From'
                )
                ->addColumn(
                    'to_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    [],
                    'To'
                )
                ->addColumn(
                    'is_active',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Is Active'
                )
                ->addColumn(
                    'conditions_serialized',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '2M',
                    [],
                    'Conditions Serialized'
                )
                ->addColumn(
                    'actions_serialized',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '2M',
                    [],
                    'Actions Serialized'
                )
                ->addColumn(
                    'stop_rules_processing',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '1'],
                    'Stop Rules Processing'
                )
                ->addColumn(
                    'sort_order',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Sort Order'
                )
                ->addColumn(
                    'simple_action',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    32,
                    [],
                    'Simple Action'
                )
                ->addColumn(
                    'discount_amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => false, 'default' => 0.0000],
                    'Discount Amount'
                )
                ->addColumn(
                    'sub_is_enable',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Is Rule Enable For Subitems'
                )
                ->addColumn(
                    'sub_simple_action',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    32,
                    [],
                    'Simple Action For Subitems'
                )
                ->addColumn(
                    'sub_discount_amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => false, 'default' => 0.0000],
                    'Discount Amount For Subitems'
                )
                ->addColumn(
                    'condition_customized',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Customized conditions'
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule', ['is_active', 'sort_order', 'to_date', 'from_date']),
                    ['is_active', 'sort_order', 'to_date', 'from_date']
                )
                ->setComment('CatalogRule');

            $installer->getConnection()->createTable($table);
        }

        /**
         * Create table 'catalogrule_product'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_rule_product'))) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mw_freegift_rule_product'))
                ->addColumn(
                    'rule_product_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Rule Product Id'
                )
                ->addColumn(
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Rule Id'
                )
                ->addColumn(
                    'from_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'From Time'
                )
                ->addColumn(
                    'to_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'To time'
                )
                ->addColumn(
                    'customer_group_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Customer Group Id'
                )
                ->addColumn(
                    'product_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Product Id'
                )
                ->addColumn(
                    'rule_gift_ids',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    [],
                    'Rule Gift Ids'
                )
                ->addColumn(
                    'action_operator',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    10,
                    ['default' => 'to_fixed'],
                    'Action Operator'
                )
                ->addColumn(
                    'action_amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => false, 'default' => '0.0000'],
                    'Action Amount'
                )
                ->addColumn(
                    'action_stop',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Action Stop'
                )
                ->addColumn(
                    'sort_order',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Sort Order'
                )
                ->addColumn(
                    'website_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Website Id'
                )
                ->addColumn(
                    'sub_simple_action',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    32,
                    [],
                    'Simple Action For Subitems'
                )
                ->addColumn(
                    'sub_discount_amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => false, 'default' => '0.0000'],
                    'Discount Amount For Subitems'
                )
                ->addColumn(
                    'condition_customized',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Condition Customized'
                )
                ->addIndex(
                    $installer->getIdxName(
                        'mw_freegift_rule_product',
                        ['rule_id', 'from_time', 'to_time', 'website_id', 'customer_group_id', 'product_id', 'sort_order'],
                        true
                    ),
                    ['rule_id', 'from_time', 'to_time', 'website_id', 'customer_group_id', 'product_id', 'sort_order'],
                    ['type' => 'unique']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product', ['customer_group_id']),
                    ['customer_group_id']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product', ['website_id']),
                    ['website_id']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product', ['from_time']),
                    ['from_time']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product', ['to_time']),
                    ['to_time']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product', ['product_id']),
                    ['product_id']
                )
                ->setComment('CatalogRule Product');

            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'catalogrule_product_price'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_rule_product_price'))) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mw_freegift_rule_product_price'))
                ->addColumn(
                    'rule_product_price_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Rule Product PriceId'
                )
                ->addColumn(
                    'rule_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    ['nullable' => false],
                    'Rule Date'
                )
                ->addColumn(
                    'customer_group_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Customer Group Id'
                )
                ->addColumn(
                    'product_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Product Id'
                )
                ->addColumn(
                    'rule_price',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => false, 'default' => '0.0000'],
                    'Rule Price'
                )
                ->addColumn(
                    'rule_gift_ids',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    [],
                    'Rule Gift Ids'
                )
                ->addColumn(
                    'website_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Website Id'
                )
                ->addColumn(
                    'latest_start_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    [],
                    'Latest StartDate'
                )
                ->addColumn(
                    'earliest_end_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    [],
                    'Earliest EndDate'
                )
                ->addIndex(
                    $installer->getIdxName(
                        'mw_freegift_rule_product_price',
                        ['rule_date', 'website_id', 'customer_group_id', 'product_id', 'rule_price'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['rule_date', 'website_id', 'customer_group_id', 'product_id', 'rule_price'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product_price', ['customer_group_id']),
                    ['customer_group_id']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product_price', ['website_id']),
                    ['website_id']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product_price', ['product_id']),
                    ['product_id']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_product_price', ['rule_price']),
                    ['rule_price']
                )
                ->setComment('CatalogRule Product Price');

            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'catalogrule_group_website'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_rule_group_website'))) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mw_freegift_rule_group_website'))
                ->addColumn(
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                    'Rule Id'
                )
                ->addColumn(
                    'customer_group_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                    'Customer Group Id'
                )
                ->addColumn(
                    'website_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                    'Website Id'
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_group_website', ['customer_group_id']),
                    ['customer_group_id']
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_group_website', ['website_id']),
                    ['website_id']
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'mw_freegift_rule_group_website',
                        'customer_group_id',
                        'customer_group',
                        'customer_group_id'
                    ),
                    'customer_group_id',
                    $installer->getTable('customer_group'),
                    'customer_group_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName('mw_freegift_rule_group_website', 'rule_id', 'mw_freegift_rule', 'rule_id'),
                    'rule_id',
                    $installer->getTable('mw_freegift_rule'),
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName('mw_freegift_rule_group_website', 'website_id', 'store_website', 'website_id'),
                    'website_id',
                    $installer->getTable('store_website'),
                    'website_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('CatalogRule Group Website');

            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'catalogrule_website'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_rule_website'))) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mw_freegift_rule_website'))
                ->addColumn(
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Rule Id'
                )
                ->addColumn(
                    'website_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Website Id'
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_website', ['website_id']),
                    ['website_id']
                )
                ->addForeignKey(
                    $installer->getFkName('mw_freegift_rule_website', 'rule_id', 'mw_freegift_rule', 'rule_id'),
                    'rule_id',
                    $installer->getTable('mw_freegift_rule'),
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName('mw_freegift_rule_website', 'website_id', 'store_website', 'website_id'),
                    'website_id',
                    $installer->getTable('store_website'),
                    'website_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Catalog Rules To Websites Relations');

            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'catalogrule_customer_group'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_rule_customer_group'))) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mw_freegift_rule_customer_group'))
                ->addColumn(
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Rule Id'
                )
                ->addColumn(
                    'customer_group_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Customer Group Id'
                )
                ->addIndex(
                    $installer->getIdxName('mw_freegift_rule_customer_group', ['customer_group_id']),
                    ['customer_group_id']
                )
                ->addForeignKey(
                    $installer->getFkName('mw_freegift_rule_customer_group', 'rule_id', 'mw_freegift_rule', 'rule_id'),
                    'rule_id',
                    $installer->getTable('mw_freegift_rule'),
                    'rule_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'mw_freegift_rule_customer_group',
                        'customer_group_id',
                        'customer_group',
                        'customer_group_id'
                    ),
                    'customer_group_id',
                    $installer->getTable('customer_group'),
                    'customer_group_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Catalog Rules To Customer Groups Relations');

            $installer->getConnection()->createTable($table);
        }


        /**
         * Create table 'salesrule'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule')
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rule Id'
            )->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Name'
            )->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Description'
            )->addColumn(
                'from_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                ['nullable' => true, 'default' => null],
                'From'
            )->addColumn(
                'to_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                ['nullable' => true, 'default' => null],
                'To'
            )->addColumn(
                'uses_per_customer',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'Uses Per Customer'
            )->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Active'
            )->addColumn(
                'conditions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Conditions Serialized'
            )->addColumn(
                'actions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Actions Serialized'
            )->addColumn(
                'stop_rules_processing',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Stop Rules Processing'
            )->addColumn(
                'is_advanced',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Is Advanced'
            )->addColumn(
                'product_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Product Ids'
            )->addColumn(
                'sort_order',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Sort Order'
            )->addColumn(
                'simple_action',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Simple Action'
            )->addColumn(
                'discount_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Discount Amount'
            )->addColumn(
                'discount_qty',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                [],
                'Discount Qty'
            )->addColumn(
                'discount_step',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Discount Step'
            )->addColumn(
                'apply_to_shipping',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Apply To Shipping'
            )->addColumn(
                'times_used',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Times Used'
            )->addColumn(
                'is_rss',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Rss'
            )->addColumn(
                'coupon_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Coupon Type'
            )->addColumn(
                'use_auto_generation',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => false, 'nullable' => false, 'default' => 0],
                'Use Auto Generation'
            )->addColumn(
                'uses_per_coupon',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => 0],
                'User Per Coupon'
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule', ['is_active', 'sort_order', 'to_date', 'from_date']),
                ['is_active', 'sort_order', 'to_date', 'from_date']
            )->setComment(
                'Salesrule'
            );
            $installer->getConnection()->createTable($table);
        }

        /**
         * Create table 'salesrule_coupon'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_coupon'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_coupon')
            )->addColumn(
                'coupon_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Coupon Id'
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Rule Id'
            )->addColumn(
                'code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Code'
            )->addColumn(
                'usage_limit',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Usage Limit'
            )->addColumn(
                'usage_per_customer',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Usage Per Customer'
            )->addColumn(
                'times_used',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Times Used'
            )->addColumn(
                'expiration_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Expiration Date'
            )->addColumn(
                'is_primary',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Is Primary'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Coupon Code Creation Date'
            )->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => true, 'default' => 0],
                'Coupon Code Type'
            )->addIndex(
                $installer->getIdxName(
                    'mw_freegift_salesrule_coupon',
                    ['code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['code'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName(
                    'mw_freegift_salesrule_coupon',
                    ['rule_id', 'is_primary'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['rule_id', 'is_primary'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_coupon', ['rule_id']),
                ['rule_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_coupon', 'rule_id', 'mw_freegift_salesrule', 'rule_id'),
                'rule_id',
                $installer->getTable('mw_freegift_salesrule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Salesrule Coupon'
            );
            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'salesrule_coupon_usage'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_coupon_usage'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_coupon_usage')
            )->addColumn(
                'coupon_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Coupon Id'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Customer Id'
            )->addColumn(
                'times_used',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Times Used'
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_coupon_usage', ['customer_id']),
                ['customer_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_coupon_usage', 'coupon_id', 'mw_freegift_salesrule_coupon', 'coupon_id'),
                'coupon_id',
                $installer->getTable('mw_freegift_salesrule_coupon'),
                'coupon_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_coupon_usage', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Salesrule Coupon Usage'
            );
            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'salesrule_customer'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_customer'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_customer')
            )->addColumn(
                'rule_customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rule Customer Id'
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Rule Id'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Customer Id'
            )->addColumn(
                'times_used',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Times Used'
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_customer', ['rule_id', 'customer_id']),
                ['rule_id', 'customer_id']
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_customer', ['customer_id', 'rule_id']),
                ['customer_id', 'rule_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_customer', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_customer', 'rule_id', 'mw_freegift_salesrule', 'rule_id'),
                'rule_id',
                $installer->getTable('mw_freegift_salesrule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Salesrule Customer'
            );
            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'salesrule_label'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_label'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_label')
            )->addColumn(
                'label_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Label Id'
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Rule Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )->addColumn(
                'label',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Label'
            )->addIndex(
                $installer->getIdxName(
                    'mw_freegift_salesrule_label',
                    ['rule_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['rule_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_label', ['store_id']),
                ['store_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_label', 'rule_id', 'mw_freegift_salesrule', 'rule_id'),
                'rule_id',
                $installer->getTable('mw_freegift_salesrule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_label', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Salesrule Label'
            );
            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'salesrule_product_attribute'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_product_attribute'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_product_attribute')
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rule Id'
            )->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Website Id'
            )->addColumn(
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Customer Group Id'
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Attribute Id'
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_product_attribute', ['website_id']),
                ['website_id']
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_product_attribute', ['customer_group_id']),
                ['customer_group_id']
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_product_attribute', ['attribute_id']),
                ['attribute_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_product_attribute', 'attribute_id', 'eav_attribute', 'attribute_id'),
                'attribute_id',
                $installer->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(
                    'mw_freegift_salesrule_product_attribute',
                    'customer_group_id',
                    'customer_group',
                    'customer_group_id'
                ),
                'customer_group_id',
                $installer->getTable('customer_group'),
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_product_attribute', 'rule_id', 'mw_freegift_salesrule', 'rule_id'),
                'rule_id',
                $installer->getTable('mw_freegift_salesrule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_product_attribute', 'website_id', 'store_website', 'website_id'),
                'website_id',
                $installer->getTable('store_website'),
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Salesrule Product Attribute'
            );
            $installer->getConnection()->createTable($table);
        }

        /**
         * Create table 'salesrule_coupon_aggregated'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_coupon_aggregated'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_coupon_aggregated')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )->addColumn(
                'period',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                ['nullable' => false],
                'Period'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Store Id'
            )->addColumn(
                'order_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                [],
                'Order Status'
            )->addColumn(
                'coupon_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                [],
                'Coupon Code'
            )->addColumn(
                'coupon_uses',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'Coupon Uses'
            )->addColumn(
                'subtotal_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Subtotal Amount'
            )->addColumn(
                'discount_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Discount Amount'
            )->addColumn(
                'total_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Total Amount'
            )->addColumn(
                'subtotal_amount_actual',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Subtotal Amount Actual'
            )->addColumn(
                'discount_amount_actual',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Discount Amount Actual'
            )->addColumn(
                'total_amount_actual',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Total Amount Actual'
            )->addColumn(
                'rule_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Rule Name'
            )->addIndex(
                $installer->getIdxName(
                    'mw_freegift_salesrule_coupon_aggregated',
                    ['period', 'store_id', 'order_status', 'coupon_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['period', 'store_id', 'order_status', 'coupon_code'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_coupon_aggregated', ['store_id']),
                ['store_id']
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_coupon_aggregated', ['rule_name']),
                ['rule_name']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_coupon_aggregated', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Coupon Aggregated'
            );
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_coupon_aggregated_updated'))) {
            $installer->getConnection()->createTable(
                $installer->getConnection()->createTableByDdl(
                    $installer->getTable('mw_freegift_salesrule_coupon_aggregated'),
                    $installer->getTable('mw_freegift_salesrule_coupon_aggregated_updated')
                )
            );
        }

        /**
         * Create table 'salesrule_coupon_aggregated_order'
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_coupon_aggregated_order'))) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mw_freegift_salesrule_coupon_aggregated_order')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )->addColumn(
                'period',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                ['nullable' => false],
                'Period'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Store Id'
            )->addColumn(
                'order_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                [],
                'Order Status'
            )->addColumn(
                'coupon_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                [],
                'Coupon Code'
            )->addColumn(
                'coupon_uses',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'Coupon Uses'
            )->addColumn(
                'subtotal_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Subtotal Amount'
            )->addColumn(
                'discount_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Discount Amount'
            )->addColumn(
                'total_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Total Amount'
            )->addColumn(
                'rule_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Rule Name'
            )->addIndex(
                $installer->getIdxName(
                    'mw_freegift_salesrule_coupon_aggregated_order',
                    ['period', 'store_id', 'order_status', 'coupon_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['period', 'store_id', 'order_status', 'coupon_code'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_coupon_aggregated_order', ['store_id']),
                ['store_id']
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_coupon_aggregated_order', ['rule_name']),
                ['rule_name']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_coupon_aggregated_order', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Coupon Aggregated Order'
            );
            $installer->getConnection()->createTable($table);
        }
        $websitesTable = $installer->getTable('store_website');
        $customerGroupsTable = $installer->getTable('customer_group');
        $rulesWebsitesTable = $installer->getTable('mw_freegift_salesrule_website');
        $rulesCustomerGroupsTable = $installer->getTable('mw_freegift_salesrule_customer_group');

        /**
         * Create table 'salesrule_website' if not exists. This table will be used instead of
         * column website_ids of main catalog rules table
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_website'))) {
            $table = $installer->getConnection()->newTable(
                $rulesWebsitesTable
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rule Id'
            )->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Website Id'
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_website', ['website_id']),
                ['website_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_website', 'rule_id', 'mw_freegift_salesrule', 'rule_id'),
                'rule_id',
                $installer->getTable('mw_freegift_salesrule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_website', 'website_id', 'store_website', 'website_id'),
                'website_id',
                $websitesTable,
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Sales Rules To Websites Relations'
            );

            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'salesrule_customer_group' if not exists. This table will be used instead of
         * column customer_group_ids of main catalog rules table
         */
        if (!$installer->getConnection()->isTableExists($installer->getTable('mw_freegift_salesrule_customer_group'))) {
            $table = $installer->getConnection()->newTable(
                $rulesCustomerGroupsTable
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rule Id'
            )->addColumn(
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Customer Group Id'
            )->addIndex(
                $installer->getIdxName('mw_freegift_salesrule_customer_group', ['customer_group_id']),
                ['customer_group_id']
            )->addForeignKey(
                $installer->getFkName('mw_freegift_salesrule_customer_group', 'rule_id', 'salesrule', 'rule_id'),
                'rule_id',
                $installer->getTable('mw_freegift_salesrule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(
                    'mw_freegift_salesrule_customer_group',
                    'customer_group_id',
                    'customer_group',
                    'customer_group_id'
                ),
                'customer_group_id',
                $customerGroupsTable,
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Sales Rules To Customer Groups Relations'
            );

            $installer->getConnection()->createTable($table);
        }
//        $installer->getConnection()
//            ->addColumn(
//                $installer->getTable('sales_flat_quote'),
//                'freegift_ids',
//                [
//                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                    'after' => 'store_id',
//                    'comment' => 'Rule Id',
//                    'nullable' => true
//                ]
//            )->addColumn(
//                $installer->getTable('sales_flat_quote'),
//                'freegift_applied_rule_ids',
//                [
//                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                    'after' => 'freegift_ids',
//                    'comment' => 'FreeGift Ids',
//                    'nullable' => true
//                ]
//            )->addColumn(
//                $installer->getTable('sales_flat_quote'),
//                'freegift_coupon_code',
//                [
//                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                    'after' => 'freegift_applied_rule_ids',
//                    'comment' => 'FreeGift Coupon',
//                    'nullable' => true
//                ]
//            );

        $installer->endSetup();
    }
}
