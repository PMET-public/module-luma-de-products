<?php

namespace MagentoEse\LumaDEProducts\Setup;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\State;
use Magento\Framework\File\Csv;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Indexer\Model\Processor;
use Magento\Store\Model\Store;

    /**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    /**
     * 
     * @var Store
     */
    private $storeView;

    /**
     * 
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * 
     * @var array
     */
    private $config;

    /**
     * 
     * @var FixtureManager
     */
    private $fixtureManager;

    /**
     * 
     * @var Csv
     */
    private $csvReader;

    /**
     * 
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * 
     * @var MagentoEse\DataInstall\Model\Import\Importer\Importer
     */
    private $importerModel;

    /**
     * 
     * @param Context $sampleDataContext 
     * @param Store $storeView 
     * @param ProductFactory $productFactory 
     * @param State $state 
     * @param ObjectManagerInterface $objectManager 
     * @return void 
     */
    public function __construct(\Magento\Framework\Setup\SampleData\Context $sampleDataContext,
                                \Magento\Store\Model\Store $storeView,
                                \Magento\Catalog\Model\ProductFactory $productFactory,
                                \Magento\Framework\App\State $state,
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
        $this->objectManager=$objectManager;

    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //Need to reindex to make sure the 2nd store index tables exist before saving products.
        //$this->index->reindexAll();

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
        $this->importerModel = $this->objectManager->create('MagentoEse\DataInstall\Model\Import\Importer\Importer');
        $this->importerModel->setEntityCode('catalog_product');
        $this->importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $this->importerModel->processImport($_productsArray);
        } catch (\Exception $e) {
            print_r($e->getMessage());
       }

        print_r($this->importerModel->getLogTrace());
        print_r($this->importerModel->getErrorMessages());
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
                $_product->setData('short_description', $_row['short_description']);

            try {
                $_product->save();
            }catch (\Exception $e){
                echo $_row['sku'] . "Failed\n";
            }
                unset($_product);

        }
        unset($this->importerModel);
    }
}
