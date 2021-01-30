<?php
namespace Sga\Merchandiser\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $_category;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Category $category
    ) {
        $this->_category = $category;

        parent::__construct($context);
    }

    public function getProductIdsByCategory($storeId, $categoryId)
    {
        $collection = $this->_category->setId($categoryId)
            ->setStoreId($storeId)
            ->getProductCollection()
            ->addOrder('position', 'ASC');

        $collection->getSelect()->group('e.entity_id');

        $productsIds = array();
        foreach ($collection as $item) {
            $productsIds[] = $item->getId();
        }
        return $productsIds;
    }
}