<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sga\Merchandiser\Model\ResourceModel\Position;

class SavePositions extends \Magento\Backend\App\Action
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
        ];

        if ($this->getRequest()->isPost()) {
            try {
                $storeId = (int)$this->getRequest()->getParam('store');
                $categoryId = (int)$this->getRequest()->getParam('category');
                $positions = explode(',', (string)$this->getRequest()->getParam('positions'));

                $this->_positionResource->savePosition($storeId, $categoryId, $positions);

                $data['message'] = __('Change are successfully save');
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
