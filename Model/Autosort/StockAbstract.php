<?php
namespace Sga\Merchandiser\Model\Autosort;

use Sga\Merchandiser\Model\Autosort\AbstractSort;

abstract class StockAbstract extends AbstractSort
{
    protected function _getStockSelect($storeId, $categoryId)
    {
        $productIds = $this->_getProductIds($storeId, $categoryId);
        if (count($productIds) === 0) {
            $productIds[] = 0;
        }

        // add child quantity
        $select = $this->_getSelect()
            ->reset()
            ->from(
                array('main_table' => $this->_getResource()->getTable('cataloginventory_stock_item')),
                array('product_id')
            )
            ->where('main_table.product_id IN ('.implode(',', $productIds).')')
            ->joinLeft(
                array('rel' => $this->_getResource()->getTable('catalog_product_relation')),
                'rel.parent_id=main_table.product_id',
                array('child_id' => 'rel.child_id')
            )
            ->joinLeft(
                array('stock_child' => $this->_getResource()->getTable('cataloginventory_stock_item')),
                'stock_child.product_id=rel.child_id',
                array('quantity' => new \Zend_Db_Expr('IF (stock_child.qty IS NOT NULL, SUM(stock_child.qty), main_table.qty)'))
            )
            ->group('main_table.product_id');

        return $select;
    }
}