<?php
namespace Sga\Merchandiser\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Sga_Merchandiser::merchandiser';

    protected $_resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        /* @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Sga_Merchandiser::merchandiser');
        $resultPage->addBreadcrumb(__('Merchandiser'), __('Merchandiser'));
        $resultPage->getConfig()->getTitle()->prepend(__('Merchandiser'));

        return $resultPage;
    }
}
