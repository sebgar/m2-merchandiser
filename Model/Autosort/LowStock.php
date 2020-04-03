<?php
namespace Sga\Merchandiser\Model\Autosort;

use Sga\Merchandiser\Model\Autosort\StockAbstract;

class Lowstock extends StockAbstract
{
    public function applyOrder($storeId, $categoryId)
    {
        $select = $this->_getStockSelect($storeId, $categoryId);

        // add order
        $select->order(new \Zend_Db_Expr('IF (stock_child.qty IS NOT NULL, SUM(stock_child.qty), main_table.qty) ASC'));

        // extract position
        $positions = array();
        $lines = $select->query()->fetchAll();
        foreach ($lines as $line) {
            $positions[] = $line['product_id'];
        }

        // save position
        $this->_savePosition($storeId, $categoryId, $positions);
    }
}