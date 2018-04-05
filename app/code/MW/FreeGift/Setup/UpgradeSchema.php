<?php
namespace MW\FreeGift\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '0.0.5') <= 0) {

            /**
             * Update 'mw_freegift_rule' table.
             */
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_rule'),'times_limit')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_rule'),
                        'times_limit',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'is_active',
                            'comment' => 'Times Limit',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_rule'),'times_used')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_rule'),
                        'times_used',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'is_active',
                            'comment' => 'Times Used',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_rule'),'gift_product_ids')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_rule'),
                        'gift_product_ids',
                        [
                            'type' => Table::TYPE_TEXT,
                            'after' => 'simple_action',
                            'comment' => 'Gift Product Ids',
                            'nullable' => true
                        ]
                    );
            }
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'gift_product_ids')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'gift_product_ids',
                        [
                            'type' => Table::TYPE_TEXT,
                            'after' => 'product_ids',
                            'comment' => 'Gift Product Ids',
                            'nullable' => true
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'promotion_message')) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('mw_freegift_salesrule'),
                    'promotion_message',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'after' => 'description',
                        'comment' => 'Promotion Message',
                        'nullable' => true
                    ]
                );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'number_of_free_gift')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'number_of_free_gift',
                        [
                            'type' => Table::TYPE_INTEGER,
                            'after' => 'promotion_message',
                            'comment' => 'Number of Free Gift',
                            'nullable' => true,
                            'default' => '1'
                        ]
                    );
            }
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'promotion_banner')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'promotion_banner',
                        [
                            'type' => Table::TYPE_TEXT,
                            'after' => 'promotion_message',
                            'comment' => 'Promotion Banner',
                            'nullable' => true
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'enable_social')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'enable_social',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'number_of_free_gift',
                            'comment' => 'Enable Social',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'google_plus')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'google_plus',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'enable_social',
                            'comment' => 'Enable Google Plus',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'like_fb')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'like_fb',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'google_plus',
                            'comment' => 'Enable Facebook Like',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'share_fb')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'share_fb',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'like_fb',
                            'comment' => 'Enable Fabook Share',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'twitter')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'twitter',
                        [
                            'type' => Table::TYPE_SMALLINT,
                            'after' => 'share_fb',
                            'comment' => 'Enable Twitter Tweet',
                            'nullable' => true,
                            'default' => '0'
                        ]
                    );
            }

            if (!$setup->getConnection()->tableColumnExists($setup->getTable('mw_freegift_salesrule'),'default_message')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('mw_freegift_salesrule'),
                        'default_message',
                        [
                            'type' => Table::TYPE_TEXT,
                            'after' => 'twitter',
                            'comment' => 'Default Message',
                            'nullable' => true,
                        ]
                    );
            }

//        }
//
//        if (version_compare($context->getVersion(), '0.0.4') <= 0) {

            /**
             * Update 'sales_flat_quote' table.
             */
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('quote'),'freegift_ids')) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable('quote'),
                        'freegift_ids',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'after' => 'store_id',
                            'comment' => 'Rule Id',
                            'nullable' => true
                        ]
                    );
            }
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('quote'),'freegift_applied_rule_ids')) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'freegift_applied_rule_ids',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'after' => 'freegift_ids',
                        'comment' => 'FreeGift Ids',
                        'nullable' => true
                    ]
                );
            }
            if (!$setup->getConnection()->tableColumnExists($setup->getTable('quote'),'freegift_coupon_code')) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'freegift_coupon_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'after' => 'freegift_applied_rule_ids',
                        'comment' => 'FreeGift Coupon',
                        'nullable' => true
                    ]
                );
            }
        }
    }
}