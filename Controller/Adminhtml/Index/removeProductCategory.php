<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sga\Merchandiser\Model\ResourceModel\Position;
use Magento\Catalog\Model\Product;

class RemoveProductCategory extends \Magento\Backend\App\Action
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
                $productId =  (int)$this->getRequest()->getParam('product');

                $this->_positionResource->removeProductCategory($categoryId, $productId);
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
