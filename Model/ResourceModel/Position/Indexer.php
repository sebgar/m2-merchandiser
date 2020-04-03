<?php
namespace Sga\Merchandiser\Model\ResourceModel\Position;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Indexer extends AbstractDb
{
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $connectionName = null
    ) {
        $this->_storeManager = $storeManager;

        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('catalog_category_product_index', 'category_id');
    }

    public function copyMerchandiserPosition($storeId = null, $categoryId = null, $productId = null)
    {
        $select = $this->getConnection()->select();

        if ((int)$storeId === 0) {
            // case all store
            foreach ($this->_storeManager->getStores() as $store) {
                $this->copyMerchandiserPosition($store->getId(), $categoryId, $productId);
            }
        } else {
            // mark all entry with position Sga_Merchandiser_Model_Config::MAX_POSITION
            $wheres = array(
                'store_id='.(int)$storeId,
            );
            if ($categoryId !== null) {
                $wheres[] = 'category_id='.(int)$categoryId;
            }
            if ($productId !== null) {
                $wheres[] = 'product_id='.(int)$productId;
            }
            $data = array(
                'position' => \Sga\Merchandiser\Helper\Config::MAX_POSITION
            );
            $this->getConnection()->update($this->getTable('catalog_category_product_index'), $data, implode(' AND ', $wheres));
            $this->getConnection()->update($this->getTable('catalog_category_product_index').'_store'.$storeId, $data, implode(' AND ', $wheres));

            // update with merchandiser position
            $select->reset()
                ->from(
                    array('global' => $this->getTable('sga_merchandiser_position')),
                    array(new \Zend_Db_Expr($storeId), 'global.category_id', 'global.product_id')
                )
                ->joinLeft(
                    array('store' => $this->getTable('sga_merchandiser_position')),
                    'store.category_id=global.category_id AND store.product_id=global.product_id AND store.store_id='.(int)$storeId,
                    array('position' => new \Zend_Db_Expr('IF(store.position IS NOT NULL, store.position, global.position)'))
                )
                ->where('global.store_id=0');

            if ($categoryId !== null) {
                $select->where('global.category_id='.(int)$categoryId);
            }

            if ($productId !== null) {
                $select->where('global.product_id='.(int)$productId);
            }

            $query = $select->insertFromSelect($this->getTable('catalog_category_product_index'), array('store_id', 'category_id', 'product_id', 'position'), true);
            $this->getConnection()->query($query);
            $query = $select->insertFromSelect($this->getTable('catalog_category_product_index').'_store'.$storeId, array('store_id', 'category_id', 'product_id', 'position'), true);
            $this->getConnection()->query($query);
        }
    }
}