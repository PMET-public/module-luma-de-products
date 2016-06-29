<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace MagentoEse\LumaDEProducts\Model;
class ArrayAdapterFactory
{
    protected $_objectManager = null;

    protected $_instanceName = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = 'MagentoEse\LumaDEProducts\Model\ArrayAdapter'
    )
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * @param array $data
     * @return \MagentoEse\LumaDEProducts\Model\ArrayAdapter
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}