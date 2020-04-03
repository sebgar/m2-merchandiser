<?php
namespace Sga\Merchandiser\Plugin\Model;

class Category
{
    protected $_indexer;

    /**
     * @param ProductRuleProcessor $productRuleProcessor
     */
    public function __construct(
        \Sga\Merchandiser\Model\ResourceModel\Position\Indexer $indexer
    ) {
        $this->_indexer = $indexer;
    }

    /**
     * @param \Magento\Catalog\Model\Category $subject
     * @param \Magento\Catalog\Model\Category $result
     * @return \Magento\Catalog\Model\Category
     */
    public function afterSave(
        \Magento\Catalog\Model\Category $subject,
        \Magento\Catalog\Model\Category $result
    ) {
        /** @var \Magento\Catalog\Model\Category $result */
        $this->_indexer->copyMerchandiserPosition(null, $subject->getId());
        return $result;
    }
}