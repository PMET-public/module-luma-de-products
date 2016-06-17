<?php

namespace MagentoEse\LumaDEProducts\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;


    /**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    protected $sampleDataContext;
    protected $storeView;
    protected $product;
    private $state;
    private $index;


    public function __construct(\Magento\Framework\Setup\SampleData\Context $sampleDataContext,
                                \Magento\Store\Model\Store $storeView,
                                \Magento\Catalog\Model\ProductFactory $productFactory,
                                \Magento\Framework\App\State $state,
                                \Magento\Indexer\Model\Processor $index)
    {

        try{
            $state->setAreaCode('adminhtml');
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }

        $this->config = require 'Config.php';
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeView = $storeView;
        $this->productFactory = $productFactory;
        $this->index = $index;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //Need to reindex to make sure the 2nd store index tables exist before saving products.
        $this->index->reindexAll();

        //get view id from view code
        $_viewId = $this->storeView->load($this->config['viewCode'])->getStoreId();

        //get category label translations
        $_fileName = $this->fixtureManager->getFixture('MagentoEse_LumaDEProducts::fixtures/Products.csv');
        $_rows = $this->csvReader->getData($_fileName);

        $_header = array_shift($_rows);

        foreach ($_rows as $_row) {
            $_product = $this->productFactory->create();
            $_data = [];
            foreach ($_row as $_key => $_value) {
                $_data[$_header[$_key]] = $_value;
            }
            $_row = $_data;
            $_product->load($_product->getIdBySku($_row['sku']));
            $_product->setStoreId($_viewId);
            $_product->setName($_row['name']);
            $_product->setData('description',$_row['description']);
            $_product->save();
            unset($_product);
        }
    }
}