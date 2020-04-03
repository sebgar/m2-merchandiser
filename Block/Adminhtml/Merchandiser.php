<?php
namespace Sga\Merchandiser\Block\Adminhtml;

use \Sga\Merchandiser\Block\Adminhtml\Merchandiser\Products;

class Merchandiser extends \Magento\Backend\Block\Template
{
    protected $_helperConfig;
    protected $_jsonSerializer;
    protected $_urlBuilder;
    protected $_scopeConfig;
    protected $_categoryCollection;

    protected $_cacheCategoryName = array();

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Sga\Merchandiser\Helper\Config $helperConfig,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
        array $data = []
    ) {
        $this->_helperConfig = $helperConfig;
        $this->_jsonSerializer = $jsonSerializer;
        $this->_urlBuilder = $urlBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_categoryCollection = $categoryCollection;

        parent::__construct($context, $data);
    }

    public function getConfigHelper()
    {
        return $this->_helperConfig;
    }

    public function getJsonSerializer()
    {
        return $this->_jsonSerializer;
    }

    public function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    public function getStoreManager()
    {
        return $this->_storeManager;
    }

    public function getMaxProducts()
    {
        return Products::MAX_PRODUCTS;
    }

    public function getListNbColumns()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('\Sga\Merchandiser\Model\System\Config\Source\Nbcolumns');
        return $model->toOptionArray();
    }

    public function getCategories()
    {
        $list = [];

        $storeId = (int)$this->getRequest()->getParam('store');

        $categories = $this->_categoryCollection->create()
            ->addAttributeToSelect('name')
            ->setStoreId($storeId);

        foreach ($categories as $category) {
            if ($category->getId() != \Magento\Catalog\Model\Category::TREE_ROOT_ID) {
                $list[$category->getId()] = $this->getCategoryPathName($category, $categories);
            }
        }

        asort($list);

        return $list;
    }

    public function getCategoryPathName($category, $categories)
    {
        $name = array();
        $path = explode('/', $category->getPath());

        // remove root category
        array_shift($path);

        foreach ($path as $catId) {
            if (isset($this->_cacheCategoryName[$catId])) {
                $name[] = $this->_cacheCategoryName[$catId];
            } else {
                $cat = $categories->getItemById($catId);
                if ($cat->getId() > 0) {
                    $this->_cacheCategoryName[$catId] = $cat->getName();
                    $name[] = $cat->getName();
                } else {
                    $name[] = '-';
                }
            }
        }

        return implode(' > ', $name);
    }

    public function getAutoSorts()
    {
        $list = [];

        $sorts = $this->_scopeConfig->get('system', 'default/merchandiser/autosort');
        if (is_array($sorts)) {
            foreach ($sorts as $sortKey => $sort) {
                $list[$sortKey] = isset($sort['label']) ? __($sort['label']) : '-';
            }
        }

        return $list;
    }
}