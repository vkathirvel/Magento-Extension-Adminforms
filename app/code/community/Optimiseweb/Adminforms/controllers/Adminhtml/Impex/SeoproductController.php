<?php

/**
 * Optimiseweb Adminforms Adminhtml Impex Seoproduct Controller
 *
 * @package     Optimiseweb_Adminforms
 * @author      Kathir Vel (sid@optimiseweb.co.uk)
 * @copyright   Copyright (c) 2014 Optimise Web
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once Mage::getModuleDir('controllers', 'Optimiseweb_Adminforms') . DS . 'Adminhtml' . DS . 'ImpexController.php';

class Optimiseweb_Adminforms_Adminhtml_Impex_SeoproductController extends Optimiseweb_Adminforms_Adminhtml_ImpexController
{

    protected $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
    protected $exportFile = 'export_seo_product.csv';
    protected $importFile = 'import_seo_product.csv';
    protected $rowCounter = 0;
    protected $successCounter = 0;
    protected $errorCounter = 0;

    /**
     * Export Product SEO Data
     */
    public function exportseoproductAction()
    {
        $option = $this->getRequest()->getParam('option');
        /* Get the products */
        $storeId = $this->getRequest()->getParam('store_id');
        if (!empty($storeId)) {
            $this->storeId = $storeId;
        }
        Mage::app()->setCurrentStore($this->storeId);
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addStoreFilter();
        $collection->addPriceData();
        $collection->addAttributeToSelect(
            array(
                    'sku',
                    'name',
                    'description',
                    'meta_title',
                    'meta_description',
                    'meta_keyword',
                    'url_key',
            )
        );
        if (!empty($option)) {
            switch ($option) {
                case 'all':
                    break;

                case 'enabled':
                    $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                    /* Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection); */
                    break;

                case 'visible':
                    $collection->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));
                    /* Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection); */
                    /* $collection->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); */
                    break;

                case 'visibleenabled':
                    $collection->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));
                    $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                    break;

                default:
                    break;
            }
        }

        /* Prepare CSV headers */
        $columnHeaders = array(
                'store_id',
                'entity_id',
                'sku',
                'name',
                'description',
                'meta_title',
                'meta_description',
                'meta_keyword',
                'url_key',
                'url',
        );
        $fileContents = $this->csvRowFromArray($columnHeaders);

        /* Loop through the product collection */
        foreach ($collection as $product) {
            $productRowValues = array(
                    $this->storeId,
                    $product->getData('entity_id'),
                    $product->getData('sku'),
                    $product->getData('name'),
                    $product->getData('description'),
                    $product->getData('meta_title'),
                    $product->getData('meta_description'),
                    $product->getData('meta_keyword'),
                    $product->getData('url_key'),
                    $product->getProductUrl(),
            );
            $fileContents .= $this->csvRowFromArray($productRowValues);
        }

        /* Export and Dump the File */
        $this->downloadCsv($this->exportFile, $fileContents);
    }

    /**
     * Import SEO
     */
    public function importseoproductAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            if (isset($_FILES['importfile']['name']) && $_FILES['importfile']['name'] != '') {
                try {
                    /* Upload the CSV file and get the data set */
                    $newDetails = $this->uploadCsvGetDataset($this->importFile);

                    /* Loop through the CSV products */
                    foreach ($newDetails as $newDetail) {
                        $this->rowCounter++;

                        /* Convert to Key Value pairs */
                        $newDetail = $this->prepNewDetail($newDetail);

                        if (!Mage::app()->isSingleStoreMode() AND array_key_exists('store_id', $newDetail) AND $newDetail['store_id']) {
                            $this->storeId = $newDetail['store_id'];
                        }

                        Mage::app()->setCurrentStore($this->storeId);
                        $model = Mage::getModel('catalog/product')->load($newDetail['entity_id'])->setStoreId($this->storeId);

                        if ($model->getId()) {
                            /* Attributes to check and update */
                            $attributes = array(
                                    'name',
                                    'description',
                                    'meta_title',
                                    'meta_description',
                                    'meta_keyword',
                                    'url_key'
                            );

                            /* Run a check and find if anything needs saving */
                            $save = $this->modelAttributeCheckAndSave($model, $newDetail, $attributes);

                            if ($save) {
                                /* Save the product data */
                                $model->save();
                                Mage::log('Saved entity_id: ' . $newDetail['entity_id'], 6, 'bespoke_import.log', TRUE);
                                $this->successCounter++;
                            }

                            /* Reset Save True or False? */
                            $save = FALSE;
                        } else {
                            Mage::log('Not found entity_id: ' . $newDetail['entity_id'], 6, 'bespoke_import.log', TRUE);
                            $this->errorCounter++;
                        }
                    }
                    /* Set the Success Message and Display a Summary */
                    $this->setSuccessSummaryMessage($this->successCounter, $this->errorCounter, $this->rowCounter);
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
                $this->_redirect('*/adminhtml_impex');
                return;
            }
            Mage::getSingleton('adminhtml/session')->addError('Import File Not Provided');
        }
        $this->_redirect('*/adminhtml_impex');
        return;
    }

}
