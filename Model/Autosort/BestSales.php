<?php
namespace Sga\Merchandiser\Model\Autosort;

use Sga\Merchandiser\Model\Autosort\AbstractSort;

class BestSales extends AbstractSort
{
    public function applyOrder($storeId, $categoryId)
    {
        $productIds = $this->_getProductIds($storeId, $categoryId);

        $select = $this->_getSelect()
            ->reset()
            ->from(
                $this->_getResource()->getTable('sales_order_item'),
                array('product_id')
            )
            ->where('product_id IN ('.implode(',', $productIds).')');

        // add filter on store
        //  => if 0 => no filter on store
        if ($storeId > 0) {
            $select->where('store_id=?', $storeId);
        }

        // add group and order on qty_ordered
        $select->group(array('product_id'))
            ->columns(array('nb' => new \Zend_Db_Expr('SUM(qty_ordered)')))
            ->order(new \Zend_Db_Expr('SUM(qty_ordered) DESC'));

        // extract position
        $positions = array();
        $lines = $select->query()->fetchAll();
        foreach ($lines as $line) {
            $positions[] = $line['product_id'];
        }

        $this->_savePosition($storeId, $categoryId, $positions);
    }
}