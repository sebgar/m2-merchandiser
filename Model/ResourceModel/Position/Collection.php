<?php
namespace Sga\Merchandiser\Model\ResourceModel\Position;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Sga\Merchandiser\Model\Position as Model;
use Sga\Merchandiser\Model\ResourceModel\Position as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
