<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sga\Merchandiser\Model\ResourceModel\Position;
use Magento\Catalog\Model\Product;

class AddProductsCategory extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Sga_Merchandiser::merchandiser';

    protected $_resultJsonFactory;
    protected $_positionResource;
    protected $_product;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Position $positionResource,
        Product $product
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_positionResource = $positionResource;
        $this->_product = $product;

        parent::__construct($context);
    }

    public function execute()
    {
        $data = [
            'message' => '',
        ];

        if ($this->getRequest()->isPost()) {
            try {
                $categoryId = (int)$this->getRequest()->getParam('category');
                $skus = explode(',', (string)$this->getRequest()->getParam('skus'));

                foreach ($skus as $sku) {
                    $productId = (int)$this->_product->getIdBySku(trim($sku));
                    if ($productId > 0) {
                        $this->_positionResource->addProductCategory($categoryId, $productId);
                    } else {
                        $data['message'] = __('Product '.$sku.' does not exists');
                    }
                }
            } catch (\Exception $e) {
                $data['message'] = $e->getMessage();
            }
        } else {
            $data['message'] = __('Not a POST request');
        }

        $result = $this->_resultJsonFactory->create();
        return $result->setData($data);
    }
}
