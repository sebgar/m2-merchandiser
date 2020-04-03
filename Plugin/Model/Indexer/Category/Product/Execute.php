<?php
namespace Sga\Merchandiser\Plugin\Model\Indexer\Category\Product;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;

class Execute
{
    protected $_indexer;

    public function __construct(
        \Sga\Merchandiser\Model\ResourceModel\Position\Indexer $indexer
    ) {
        $this->_indexer = $indexer;
    }

    /**
     * @param AbstractAction $subject
     * @param AbstractAction $result
     * @return AbstractAction
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(AbstractAction $subject, AbstractAction $result)
    {
        $this->_indexer->copyMerchandiserPosition(null, null);
        return $result;
    }
}
