<?php
namespace Sga\Merchandiser\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    protected $_scopeConfig;

    const MAX_POSITION = 999999;

    const XML_PATH_MERCHANDISER_NB_COLUMN_DEFAULT = 'merchandiser/general/nb_column_default';

    const XML_PATH_MERCHANDISER_PRODUCTS_FILTERS_OUT_OF_STOCK = 'merchandiser/products_filters/out_of_stock';
    const XML_PATH_MERCHANDISER_PRODUCTS_FILTERS_DISABLED = 'merchandiser/products_filters/disabled';
    const XML_PATH_MERCHANDISER_PRODUCTS_FILTERS_NOT_VISIBLE = 'merchandiser/products_filters/not_visible';

    const XML_PATH_MERCHANDISER_THRESHOLD_COLOR_QUANTITY = 'merchandiser/threshold_color/qty';
    const XML_PATH_MERCHANDISER_THRESHOLD_COLOR_OUT_OF_STOCK = 'merchandiser/threshold_color/out_of_stock';

    const XML_PATH_MERCHANDISER_SALES_STATUSES = 'merchandiser/sales/statuses';

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context
    ){
        $this->_scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    public function getNbColumnDefault($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_MERCHANDISER_NB_COLUMN_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isDisplayOutOfStock($store = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_MERCHANDISER_PRODUCTS_FILTERS_OUT_OF_STOCK,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isDisplayDisabled($store = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_MERCHANDISER_PRODUCTS_FILTERS_DISABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isDisplayNotVisible($store = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_MERCHANDISER_PRODUCTS_FILTERS_NOT_VISIBLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getThresholdsColorQty($store = null)
    {
        $value = $this->_scopeConfig->getValue(
            self::XML_PATH_MERCHANDISER_THRESHOLD_COLOR_QUANTITY,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $data = explode('|', $value);

        $conf = array();
        foreach ($data as $d) {
            $parts = explode(',', $d);
            if (count($parts) == 2) {
                $conf['list'][] = array(
                    'threshold' => (int)$parts[0],
                    'color' => $parts[1],
                );
            } else {
                $conf['default'] = $parts[0];
            }
        }

        return $conf;
    }

    public function getThresholdsColorOutOfStock($store = null)
    {
        $value = $this->_scopeConfig->getValue(
            self::XML_PATH_MERCHANDISER_THRESHOLD_COLOR_OUT_OF_STOCK,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $data = explode('|', $value);

        $conf = array();
        foreach ($data as $d) {
            $parts = explode(',', $d);
            if (count($parts) == 2) {
                $conf['list'][] = array(
                    'threshold' => (int)$parts[0],
                    'color' => $parts[1],
                );
            } else {
                $conf['default'] = $parts[0];
            }
        }

        return $conf;
    }

    public function getSalesStatuses($store = null)
    {
        $value = (string)$this->_scopeConfig->getValue(
            self::XML_PATH_MERCHANDISER_SALES_STATUSES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $value !== '' ? explode(',', $value) : array();
    }
}