<?php
namespace Sga\Merchandiser\Block\Adminhtml\Merchandiser\Products;

class Item extends \Magento\Backend\Block\Template
{
    protected $_helper;
    protected $_helperConfig;
    protected $_urlBuilder;
    protected $_productStatus;
    protected $_productVisibility;
    protected $_currencyManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Sga\Merchandiser\Helper\Data $helper,
        \Sga\Merchandiser\Helper\Config $helperConfig,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Pricing\PriceCurrencyInterface $currencyManager,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_helperConfig = $helperConfig;
        $this->_urlBuilder = $urlBuilder;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
        $this->_currencyManager = $currencyManager;

        parent::__construct($context, $data);
    }

    public function getHelperConfig()
    {
        return $this->_helperConfig;
    }

    public function getProductStatus()
    {
        return $this->_productStatus;
    }

    public function getProductVisibility()
    {
        return $this->_productVisibility;
    }

    public function getStoreManager()
    {
        return $this->_storeManager;
    }

    public function getCurrencyManager()
    {
        return $this->_currencyManager;
    }
}