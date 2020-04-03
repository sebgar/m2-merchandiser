<?php
namespace Sga\Merchandiser\Model\Autosort;

use Magento\Framework\Model\AbstractModel;

abstract class AbstractSort extends AbstractModel
{
    protected $_helper;
    protected $_positionResource;
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Sga\Merchandiser\Helper\Data $helper,
        \Sga\Merchandiser\Model\ResourceModel\Position $positionResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_positionResource = $positionResource;
        $this->_storeManager = $storeManager;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init(\Magento\Catalog\Model\ResourceModel\Product::class);
        return $this;
    }

    abstract public function applyOrder($storeId, $categoryId);

    protected function _getSelect()
    {
        return $this->_getResource()->getConnection()->select();
    }

    protected function _getProductIds($storeId, $categoryId)
    {
        return $this->_helper->getProductIdsByCategory($storeId, $categoryId);
    }

    protected function _savePosition($storeId, $categoryId, array $positions)
    {
        if (count($positions) > 0) {
            $this->_positionResource->savePosition($storeId, $categoryId, $positions);
        } else {
            throw new \Exception(__('No product found, no modification on position'));
        }
    }
}