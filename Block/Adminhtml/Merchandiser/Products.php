<?php
namespace Sga\Merchandiser\Block\Adminhtml\Merchandiser;

class Products extends \Magento\Backend\Block\Template
{
    const MAX_PRODUCTS = 50;

    protected $_total;

    protected $_helper;
    protected $_helperConfig;
    protected $_urlBuilder;
    protected $_productCollectionFactory;
    protected $_productStatus;
    protected $_productVisibility;
    protected $_stockStatus;
    protected $_orderItemCollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Sga\Merchandiser\Helper\Data $helper,
        \Sga\Merchandiser\Helper\Config $helperConfig,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Status $stockStatus,
        \Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItemCollection,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_helperConfig = $helperConfig;
        $this->_urlBuilder = $urlBuilder;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
        $this->_stockStatus = $stockStatus;
        $this->_orderItemCollection = $orderItemCollection;

        parent::__construct($context, $data);
    }

    public function getHelperConfig()
    {
        return $this->_helperConfig;
    }

    public function getTotal()
    {
        if (!isset($this->_total)) {
            $this->getProducts();
        }
        return $this->_total;
    }

    public function getProducts()
    {
        $storeId = (int)$this->getRequest()->getParam('store');
        $categoryId = (int)$this->getRequest()->getParam('category');
        $page = (int)$this->getRequest()->getParam('p');

        $this->_total = 0;
        $list = [];

        $productIds = $this->_helper->getProductIdsByCategory($storeId, $categoryId);
        if (count($productIds) > 0) {
            $collection = $this->_productCollectionFactory->create()
                ->setStoreId($storeId)
                ->addStoreFilter($storeId)
                ->addFieldToFilter('entity_id', array('in' => $productIds))
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('thumbnail')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('visibility')
                ->setCurPage($page)
                ->setPageSize(self::MAX_PRODUCTS);

            // filter on status
            if (!$this->_helperConfig->isDisplayDisabled()) {
                $collection->addAttributeToFilter('status', array('in' => $this->_productStatus->getVisibleStatusIds()));
            }

            // filter on visibility
            if (!$this->_helperConfig->isDisplayNotVisible()) {
                $collection->addAttributeToFilter('visibility', array('in' => $this->_productVisibility->getVisibleInCatalogIds()));
            }

            // add price data
            if ($storeId === 0) {
                // take price on eav
                $collection->addAttributeToSelect('price');
            } else {
                // take price from price index
                $collection->getSelect()
                    ->joinLeft(
                        array('price_index' => $collection->getTable('catalog_product_index_price')),
                        'price_index.entity_id=e.entity_id AND price_index.website_id='.$this->_storeManager->getStore($storeId)->getWebsite()->getId(),
                        array('price' => 'price_index.price', 'final_price' => 'price_index.final_price')
                    );
            }

            // add other attributes
            $attributes = $this->_scopeConfig->get('system', 'default/merchandiser/attributes');
            if (is_array($attributes)) {
                $attributes = array_keys($attributes);
                $collection->addAttributeToSelect($attributes);
            }

            // add position
            $collection->getSelect()
                ->joinLeft(
                    array('position_global' => $collection->getTable('sga_merchandiser_position')),
                    'position_global.product_id=e.entity_id AND position_global.category_id='.$categoryId.' AND position_global.store_id=0',
                    array('position' => 'position_global.position')
                );

            if ($storeId > 0) {
                $collection->getSelect()
                    ->joinLeft(
                        array('position_store' => $collection->getTable('sga_merchandiser_position')),
                        'position_store.product_id=e.entity_id AND position_store.category_id='.$categoryId.' AND position_store.store_id='.$storeId,
                        array('position' => new \Zend_Db_Expr('IF(position_store.position IS NOT NULL, position_store.position, position_global.position)'))
                    );
            }

            // add order on position
            $maxPosition = \Sga\Merchandiser\Helper\Config::MAX_POSITION;
            if ($storeId === 0) {
                $collection->getSelect()->order(new \Zend_Db_Expr('COALESCE(position, '.$maxPosition.') ASC'));
            } else {
                $collection->getSelect()->order(new \Zend_Db_Expr('IF(position_store.position IS NOT NULL, COALESCE(position_store.position, '.$maxPosition.'), COALESCE(position_global.position, '.$maxPosition.')) ASC'));
            }

            // add stock status
            $collection->joinTable(
                array('cisi' => $collection->getTable('cataloginventory_stock_item')),
                'product_id=entity_id',
                array(
                    'inventory_in_stock' => 'is_in_stock',
                    'quantity' => 'qty'
                ),
                null,
                'left'
            );

            // add child quantity
            $collection->joinTable(array('rel' => $collection->getTable('catalog_product_relation')), 'parent_id=entity_id',
                array('child_id' => 'child_id'),
                null,
                'left'
            );
            $collection->joinTable(array('stock_child' => $collection->getTable('cataloginventory_stock_item')), 'product_id=child_id',
                array('child_quantity' => new \Zend_Db_Expr('SUM(stock_child.qty)')),
                ($this->_helperConfig->isDisplayOutOfStock() ? null : '{{table}}.qty > 0'),
                'left'
            );

            $collection->getSelect()->group('e.entity_id');

            $this->_total = $collection->getSize();
            if (($page - 1) * self::MAX_PRODUCTS < $this->_total) {
                $list = $collection->getItems();
            }
        }

        return $list;
    }

    public function addSalesInfos(array $products)
    {
        $productIds = array();
        foreach ($products as $product) {
            $productIds[] = $product->getId();
        }

        if (count($productIds) > 0) {
            $storeId = (int)$this->getRequest()->getParam('store');

            $collection = $this->_orderItemCollection->addFieldToSelect('product_id')
                ->addFieldToFilter('product_id', array('in' => $productIds));

            $statuses = $this->_helperConfig->getSalesStatuses();
            if (count($statuses) > 0) {
                $collection->getSelect()
                    ->joinInner(
                        array('order' => $collection->getTable('sales_order')),
                        'order.entity_id=main_table.order_id'
                    );

                $collection->addFieldToFilter('order.status', array('in' => $statuses));
            }

            // add filter on store
            //  => if 0 => no filter on store
            if ($storeId > 0) {
                $collection->addFieldToFilter('main_table.store_id', $storeId);
            }

            // add group and order on qty_ordered
            $collection->getSelect()
                ->group(array('product_id'))
                ->columns(array('nb' => new \Zend_Db_Expr('SUM(qty_ordered)')))
                ->order(new \Zend_Db_Expr('SUM(qty_ordered) DESC'));

            foreach ($collection as $item) {
                if (isset($products[$item->getProductId()])) {
                    $products[$item->getProductId()]->setNbSales($item->getNb());
                }
            }
        }
    }

    public function addChildStockInfos(array $products)
    {
        $productIds = array();
        foreach ($products as $product) {
            $productIds[] = $product->getId();
        }

        if (count($productIds) > 0) {
            $collection = $this->_productCollectionFactory->create();

            // add group and order on qty_ordered
            $lines = $collection->getSelect()
                ->reset()
                ->from(
                    array('r' => $collection->getTable('catalog_product_relation')),
                    array('parent_id')
                )
                ->joinInner(
                    array('si' => $collection->getTable('cataloginventory_stock_item')),
                    'r.child_id = si.product_id',
                    array('is_in_stock', 'nb' => new \Zend_Db_Expr('COUNT(si.product_id)'))
                )
                ->where('r.parent_id IN ('.implode(',', $productIds).')')
                ->group(array('r.parent_id', 'si.is_in_stock'))
                ->query()
                ->fetchAll();

            foreach ($lines as $line) {
                if (isset($products[$line['parent_id']])) {
                    if ($line['is_in_stock'] === '0') {
                        $products[$line['parent_id']]->setNbOutOfStock((int)$products[$line['parent_id']]->getNbOutOfStock() + $line['nb']);
                    } elseif ($line['is_in_stock'] === '1') {
                        $products[$line['parent_id']]->setNbInStock((int)$products[$line['parent_id']]->getNbInStock() + $line['nb']);
                    }

                    $products[$line['parent_id']]->setNbTotalStock((int)$products[$line['parent_id']]->getNbTotalStock() + $line['nb']);
                }
            }
        }
    }
}