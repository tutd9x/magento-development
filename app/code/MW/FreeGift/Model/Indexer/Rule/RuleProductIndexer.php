<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Model\Indexer\Rule;

use MW\FreeGift\Model\Indexer\AbstractIndexer;

class RuleProductIndexer extends AbstractIndexer
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doExecuteList($ids)
    {
        $this->indexBuilder->reindexFull();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doExecuteRow($id)
    {
        $this->indexBuilder->reindexFull();
    }
}
