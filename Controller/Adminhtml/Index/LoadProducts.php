<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class LoadProducts extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Sga_Merchandiser::merchandiser';

    protected $_resultJsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->_loadLayout('merchandiser_index_loadproducts');

        $result = $this->_resultJsonFactory->create();
        $data = [
            'html' => $this->_view->getLayout()->getOutput(),
            'total' => $this->_view->getLayout()->getBlock('merchandiser.products')->getTotal(),
        ];
        return $result->setData($data);
    }

    protected function _loadLayout($handles)
    {
        $this->_view->getLayout()->getUpdate()->addHandle($handles);
        $this->_view->generateLayoutBlocks();
    }
}
