<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sga\Merchandiser\Model\ResourceModel\Position;

class CheckOverload extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Sga_Merchandiser::merchandiser';

    protected $_resultJsonFactory;
    protected $_positionResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Position $positionResource
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_positionResource = $positionResource;

        parent::__construct($context);
    }

    public function execute()
    {
        $data = [
            'message' => '',
            'overload' => 0,
        ];

        if ($this->getRequest()->isPost()) {
            try {
                $storeId = (int)$this->getRequest()->getParam('store');
                $categoryId = (int)$this->getRequest()->getParam('category');

                if ($storeId > 0) {
                    $data['overload'] = (int)$this->_positionResource->checkOverload($storeId, $categoryId);
                } else {
                    $data['message'] = __('Overload not possible on global');
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
