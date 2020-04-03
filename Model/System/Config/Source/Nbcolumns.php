<?php
namespace Sga\Merchandiser\Model\System\Config\Source;

class Nbcolumns
{
    public function toOption()
    {
        return [
            ['value' => '', 'label' => __('No column')],
            ['value' => '3', 'label' => '3'],
            ['value' => '4', 'label' => '4'],
            ['value' => '5', 'label' => '5'],
            ['value' => '6', 'label' => '6']
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            '' => __('No column'),
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6'
        ];
    }
}