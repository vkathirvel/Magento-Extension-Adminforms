<?php

/**
 * Optimiseweb Adminforms Adminhtml Impex Productprice Controller
 *
 * @package     Optimiseweb_Adminforms
 * @author      Kathir Vel (sid@optimiseweb.co.uk)
 * @copyright   Copyright (c) 2014 Optimise Web
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once Mage::getModuleDir('controllers', 'Optimiseweb_Adminforms') . DS . 'Adminhtml' . DS . 'ImpexController.php';

class Optimiseweb_Adminforms_Adminhtml_Impex_ProductpriceController extends Optimiseweb_Adminforms_Adminhtml_ImpexController
{

    protected $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
    protected $exportFile = 'export_product_prices.csv';
    protected $importFile = 'import_product_prices.csv';
    protected $rowCounter = 0;
    protected $successCounter = 0;
    protected $errorCounter = 0;

    /**
     * Export Product SEO Data
     */
    public function exportproductpriceAction()
    {
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
                    'cost',
                    'price',
                    'special_price',
                    'special_from_date',
                    'special_to_date',
            )
        );
        $collection->addAttributeToFilter('type_id', array('neq' => Mage_Catalog_Model_Product_Type::TYPE_GROUPED));

        /* Prepare CSV headers */
        $columnHeaders = array(
                'store_id',
                'entity_id',
                'sku',
                'name',
                'cost',
                'price',
                'special_price',
                'special_from_date',
                'special_to_date',
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
                    $product->getData('cost'),
                    $product->getData('price'),
                    $product->getData('special_price'),
                    $product->getData('special_from_date'),
                    $product->getData('special_to_date'),
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
    public function importproductpriceAction()
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


                        if ($model !== FALSE) {
                            /* Attributes to check and update */
                            $attributes = array(
                                    'name',
                                    'cost',
                                    'price',
                                    'special_price',
                                    'special_from_date',
                                    'special_to_date',
                            );

                            /* Formatting Decimals */
                            if ($newDetail['cost'] != NULL) {
                                $newDetail['cost'] = number_format($newDetail['cost'], 4, '.', '');
                            }
                            if ($newDetail['price'] != NULL) {
                                $newDetail['price'] = number_format($newDetail['price'], 4, '.', '');
                            }
                            if ($newDetail['special_price'] != NULL) {
                                $newDetail['special_price'] = number_format($newDetail['special_price'], 4, '.', '');
                            }
                            /* Formatting Dates */
                            if ($newDetail['special_from_date'] != NULL) {
                                $newDetail['special_from_date'] = Mage::app()->getLocale()->date($newDetail['special_from_date'], Zend_Date::DATE_SHORT)->toString('YYYY-MM-dd') . ' 00:00:00';
                            }
                            if ($newDetail['special_to_date'] != NULL) {
                                $newDetail['special_to_date'] = Mage::app()->getLocale()->date($newDetail['special_to_date'], Zend_Date::DATE_SHORT)->toString('YYYY-MM-dd') . ' 00:00:00';
                            }

                            /* Run a check and find if anything needs saving */
                            $save = $this->modelAttributeCheckAndSave($model, $newDetail, $attributes);

                            if ($save) {
                                /* Save the product data */
                                $model->save();
                                $this->successCounter++;
                            }

                            /* Reset Save True or False? */
                            $save = FALSE;
                        } else {
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
