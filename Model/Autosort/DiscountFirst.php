<?php
namespace Sga\Merchandiser\Model\Autosort;

use Sga\Merchandiser\Model\Autosort\AbstractSort;

class DiscountFirst extends AbstractSort
{
    public function applyOrder($storeId, $categoryId)
    {
        if ($storeId > 0) {
            $productIds = $this->_getProductIds($storeId, $categoryId);

            $select = $this->_getSelect()
                ->reset()
                ->from(
                    $this->_getResource()->getTable('catalog_product_index_price'),
                    'entity_id'
                )
                ->where('entity_id IN ('.implode(',', $productIds).')')
                ->where('website_id=?', $this->_storeManager->getStore($storeId)->getWebsite()->getId())
                ->group(array('entity_id'))
                ->order('IF (final_price <> price, 1, 2) ASC');

            // extract position
            $positions = array();
            $lines = $select->query()->fetchAll();
            foreach ($lines as $line) {
                $positions[] = $line['entity_id'];
            }

            $this->_savePosition($storeId, $categoryId, $positions);
        } else {
            throw new \Exception(__('Cant apply discount sort on global, price are on store, no modification on position'));
        }
    }
}