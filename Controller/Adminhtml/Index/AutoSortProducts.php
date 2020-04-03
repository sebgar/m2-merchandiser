<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Sga\Merchandiser\Model\ResourceModel\Position;
use Magento\Framework\App\Config\ScopeConfigInterface;

class AutoSortProducts extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Sga_Merchandiser::merchandiser';

    protected $_resultJsonFactory;
    protected $_positionResource;
    protected $_scopeConfig;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Position $positionResource,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_positionResource = $positionResource;
        $this->_scopeConfig = $scopeConfig;

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
                $sort = (string)$this->getRequest()->getParam('sort');

                $sorts = $this->_scopeConfig->get('system', 'default/merchandiser/autosort/'.$sort);
                if (is_array($sorts)) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $model = $objectManager->create($sorts['model']);
                    $model->applyOrder($storeId, $categoryId);
                } else {
                    throw new Exception('Sort data is unknown');
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
