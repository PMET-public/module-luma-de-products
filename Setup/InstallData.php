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

    private $sampleDataContext;
    private $storeView;
    private $product;
    private $state;
    private $index;
    private $objectManager;


    public function __construct(\Magento\Framework\Setup\SampleData\Context $sampleDataContext,
                                \Magento\Store\Model\Store $storeView,
                                \Magento\Catalog\Model\ProductFactory $productFactory,
                                \Magento\Framework\App\State $state,
                                \Magento\Indexer\Model\Processor $index,
                                \Magento\Framework\ObjectManagerInterface   $objectManager)
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
        $this->objectManager=$objectManager;

    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //Need to reindex to make sure the 2nd store index tables exist before saving products.
        $this->index->reindexAll();

        //get view id from view code
        $_viewId = $this->storeView->load($this->config['viewCode'])->getStoreId();

        //get main product translations
        $_fileName = $this->fixtureManager->getFixture('MagentoEse_LumaDEProducts::fixtures/Products.csv');
        $_rows = $this->csvReader->getData($_fileName);

        $_header = array_shift($_rows);
        $_productsArray = array();
        foreach ($_rows as $_row) {
            $_productsArray[] = array_combine($_header, $_row);
        }
        $this->importerModel  = $this->objectManager->create('MagentoEse\LumaDEProducts\Model\Importer');
        try {
            $this->importerModel->processImport($_productsArray);
        } catch (\Exception $e) {
            print_r($e->getMessage());
       }

        //print_r($this->importerModel->getLogTrace());
        //print_r($this->importerModel->getErrorMessages());
        //get translations for downloadable and bundled products
        $_fileName = $this->fixtureManager->getFixture('MagentoEse_LumaDEProducts::fixtures/DownloadsAndGroups.csv');
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
                $_product->setData('description', $_row['description']);

            try {
                $_product->save();
            }catch (Exception $e){
                echo $_row['sku'] . "Failed\n";
            }
                unset($_product);

        }
    }
}