<?php
namespace Sga\Merchandiser\Model\Autosort;

use Sga\Merchandiser\Model\Autosort\AbstractSort;

class Standard extends AbstractSort
{
    public function applyOrder($storeId, $categoryId)
    {
        $productIds = $this->_getProductIds($storeId, $categoryId);

        $this->_savePosition($storeId, $categoryId, $productIds);
    }
}