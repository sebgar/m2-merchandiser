<?php
namespace Sga\Merchandiser\Model;

use Sga\Merchandiser\Api\Data\PositionInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Position extends AbstractModel implements IdentityInterface, PositionInterface
{
    const CACHE_TAG = 'merchandiser_position';

    protected $_eventPrefix = 'merchandiser_position';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sga\Merchandiser\Model\ResourceModel\Position::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}