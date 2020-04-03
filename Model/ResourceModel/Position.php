<?php
namespace Sga\Merchandiser\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Position extends AbstractDb
{
    protected $_indexerPosition;
    protected $_helper;

    public function __construct(
        Context $context,
        \Sga\Merchandiser\Model\ResourceModel\Position\Indexer $indexerPosition,
        \Sga\Merchandiser\Helper\Data $helper,
        $connectionName = null
    ) {
        $this->_indexerPosition = $indexerPosition;
        $this->_helper = $helper;

        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('sga_merchandiser_position','id');
    }

    public function checkOverload($storeId, $categoryId)
    {
        $lines = $this->getConnection()->select()
            ->reset()
            ->from($this->getTable('sga_merchandiser_position'), array('nb' => new \Zend_Db_Expr('COUNT(*)')))
            ->where('store_id='.(int)$storeId)
            ->where('category_id='.(int)$categoryId)
            ->query()
            ->fetchAll();

        return isset($lines[0]['nb']) && $lines[0]['nb'] > 0 ? true : false;
    }

    public function removeOverload($storeId, $categoryId)
    {
        // clean table merchandiser
        $cond = array(
            'store_id=?' => $storeId,
            'category_id=?' => $categoryId
        );
        $this->getConnection()->delete($this->getTable('sga_merchandiser_position'), $cond);

        // launch copy on magento index
        $this->_indexerPosition->copyMerchandiserPosition($storeId, $categoryId);
    }

    public function savePosition($storeId, $categoryId, array $productsPosition)
    {
        // get all product of category
        $productIds = $this->_helper->getProductIdsByCategory($storeId, $categoryId);

        // start by delete all productIds not in list
        $cond = array(
            'product_id NOT IN ('.implode(',', $productIds).')' => 1,
            'category_id=?' => $categoryId
        );
        $this->getConnection()->delete($this->getTable('sga_merchandiser_position'), $cond);

        // then create / update all products position to Sga_Merchandiser_Model_Config::MAX_POSITION to put all products at end
        foreach ($productIds as $productId) {
            $data = array(
                'store_id' => $storeId,
                'category_id' => $categoryId,
                'product_id' => $productId,
                'position' => \Sga\Merchandiser\Helper\Config::MAX_POSITION,
            );
            $this->getConnection()->insertOnDuplicate($this->getTable('sga_merchandiser_position'), $data, array('position'));
        }

        // then apply new position
        foreach ($productsPosition as $i => $productId) {
            $data = array(
                'store_id' => $storeId,
                'category_id' => $categoryId,
                'product_id' => $productId,
                'position' => $i+1,
            );
            $this->getConnection()->insertOnDuplicate($this->getTable('sga_merchandiser_position'), $data, array('position'));
        }

        // launch copy on magento index
        $this->_indexerPosition->copyMerchandiserPosition($storeId, $categoryId);
    }

    public function applyToGlobal($storeId, $categoryId)
    {
        // copy store to global
        $select = $this->getConnection()->select();
        $select->reset()
            ->from(
                array($this->getTable('sga_merchandiser_position')),
                array(new \Zend_Db_Expr(0), 'category_id', 'product_id', 'position')
            )
            ->where('store_id=?', $storeId)
            ->where('category_id=?', $categoryId);

        $query = $select->insertFromSelect($this->getTable('sga_merchandiser_position'), array('store_id', 'category_id', 'product_id', 'position'), true);
        $this->getConnection()->query($query);

        // launch copy on magento index
        $this->_indexerPosition->copyMerchandiserPosition(null, $categoryId);
    }

    public function addProductCategory($categoryId, $productId)
    {
        // insert product into category
        $cond = array(
            'product_id' => $productId,
            'category_id' => $categoryId,
            'position' => 0
        );
        $this->getConnection()->insertOnDuplicate($this->getTable('catalog_category_product'), $cond, array('position'));

        // insert into merchandiser position
        $cond = array(
            'store_id' => 0,
            'product_id' => $productId,
            'category_id' => $categoryId,
            'position' => 0
        );
        $this->getConnection()->insertOnDuplicate($this->getTable('sga_merchandiser_position'), $cond, array('position'));

        // launch copy on magento index
        $this->_indexerPosition->copyMerchandiserPosition(null, $categoryId);
    }

    public function removeProductCategory($categoryId, $productId)
    {
        // delete product from category
        $cond = array(
            'product_id=?' => $productId,
            'category_id=?' => $categoryId
        );
        $this->getConnection()->delete($this->getTable('catalog_category_product'), $cond);

        // delete all merchandiser positions
        $cond = array(
            'product_id=?' => $productId,
            'category_id=?' => $categoryId
        );
        $this->getConnection()->delete($this->getTable('sga_merchandiser_position'), $cond);

        // launch copy on magento index
        $this->_indexerPosition->copyMerchandiserPosition(null, $categoryId);
    }
}
