<?php
namespace Sga\Merchandiser\Model\Autosort;

use Sga\Merchandiser\Model\Autosort\AbstractSort;

class SubCategories extends AbstractSort
{
    public function applyOrder($storeId, $categoryId)
    {
        // get all sub categories
        $subCatIds = $this->_getSubCatIds($categoryId);
        if (count($subCatIds) > 0) {
            // get all product in main category
            $productIds = $this->_getProductIds($storeId, $categoryId);

            // for each sub category, search product id and position
            $positions = array();
            foreach ($subCatIds as $subCatId) {
                // search
                $ids = $this->_getSubCatProductPosition($storeId, $subCatId, $productIds);

                if (count($ids) > 0){
                    // push
                    foreach ($ids as $id) {
                        $positions[] = $id;
                    }

                    // remove ids foucn to productIds
                    $productIds = array_diff($productIds, $ids);
                }
            }

            // push productIds left in positions
            foreach ($productIds as $id) {
                $positions[] = $id;
            }

            // save position
            $this->_savePosition($storeId, $categoryId, $positions);
        } else {
            throw new \Exception(__('Category have no sub categrories'));
        }
    }

    protected function _getSubCatIds($categoryId)
    {
        $subCats = $this->_getSelect()
            ->reset()
            ->from($this->_getResource()->getTable('catalog_category_entity'), array('entity_id'))
            ->where('path like "%/'.$categoryId.'/%"')
            ->order('position ASC')
            ->query()
            ->fetchAll();

        $subCatIds = array();
        foreach ($subCats as $cat) {
            $subCatIds[] = $cat['entity_id'];
        }
        return $subCatIds;
    }

    protected function _getSubCatProductPosition($storeId, $subCatId, array $productIds)
    {
        $ids = array();
        if (count($productIds) > 0) {
            $select = $this->_getSelect()
                ->reset()
                ->from(
                    array('rel' => $this->_getResource()->getTable('catalog_category_product')),
                    array('rel.product_id')
                )
                ->joinLeft(
                    array('position_global' => $this->_getResource()->getTable('sga_merchandiser_position')),
                    'position_global.product_id=rel.product_id AND position_global.category_id='.$subCatId.' AND position_global.store_id=0',
                    array('position' => 'position_global.position')
                )
                ->where('rel.product_id IN ('.implode(',', $productIds).')')
                ->where('rel.category_id=?', $subCatId);

            // add order on position
            $maxPos = \Sga\Merchandiser\Helper\Config::MAX_POSITION;
            if ($storeId === 0) {
                $select->order(new \Zend_Db_Expr('IF(position_global.position IS NOT NULL, COALESCE(position_global.position, '.$maxPos.'), COALESCE(rel.position, '.$maxPos.')) ASC'));
            } else {
                $select->joinLeft(
                    array('position_store' => $this->_getResource()->getTable('sga_merchandiser_position')),
                    'position_store.product_id=rel.product_id AND position_store.category_id='.$subCatId.' AND position_store.store_id='.$storeId,
                    array('position' => new \Zend_Db_Expr('IF(position_store.position IS NOT NULL, position_store.position, position_global.position)'))
                )
                    ->order(new \Zend_Db_Expr('IF(position_store.position IS NOT NULL, COALESCE(position_store.position, '.$maxPos.'), IF(position_global.position IS NOT NULL, COALESCE(position_global.position, '.$maxPos.'), COALESCE(rel.position, '.$maxPos.'))) ASC'));
            }

            $products = $select->query()
                ->fetchAll();

            foreach ($products as $product) {
                $ids[] = $product['product_id'];
            }
        }
        return $ids;
    }
}