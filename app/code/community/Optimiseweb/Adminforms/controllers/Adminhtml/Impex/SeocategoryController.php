<?php

/**
 * Optimiseweb Adminforms Adminhtml Impex Seocategory Controller
 *
 * @package     Optimiseweb_Adminforms
 * @author      Kathir Vel (sid@optimiseweb.co.uk)
 * @copyright   Copyright (c) 2014 Optimise Web
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once Mage::getModuleDir('controllers', 'Optimiseweb_Adminforms') . DS . 'Adminhtml' . DS . 'ImpexController.php';

class Optimiseweb_Adminforms_Adminhtml_Impex_SeocategoryController extends Optimiseweb_Adminforms_Adminhtml_ImpexController
{

    protected $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
    protected $exportFile = 'export_seo_category.csv';
    protected $importFile = 'import_seo_category.csv';
    protected $rowCounter = 0;
    protected $successCounter = 0;
    protected $errorCounter = 0;

    /**
     * Export SEO Data
     */
    public function exportseocategoryAction()
    {
        /* Get the categorys */
        $storeId = $this->getRequest()->getParam('store_id');
        if (!empty($storeId)) {
            $this->storeId = $storeId;
        }
        Mage::app()->setCurrentStore($this->storeId);
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->addAttributeToSelect('*');

        /* Prepare CSV headers */
        $columnHeaders = array(
                'store_id',
                'entity_id',
                'name',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'url_key',
                'url_for_reference',
        );
        $fileContents = $this->csvRowFromArray($columnHeaders);

        /* Loop through the category collection */
        foreach ($collection as $category) {
            $categoryRowValues = array(
                    $this->storeId,
                    $category->getData('entity_id'),
                    $category->getData('name'),
                    $category->getData('meta_title'),
                    $category->getData('meta_description'),
                    $category->getData('meta_keywords'),
                    $category->getData('url_key'),
                    $category->getUrl(),
            );
            $fileContents .= $this->csvRowFromArray($categoryRowValues);
        }

        /* Export and Dump the File */
        $this->downloadCsv($this->exportFile, $fileContents);
    }

    /**
     * Import SEO Data
     */
    public function importseocategoryAction()
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
                        $model = Mage::getModel('catalog/category')->load($newDetail['entity_id'])->setStoreId($this->storeId);

                        if ($model->getId()) {
                            /* Attributes to check and update */
                            $attributes = array(
                                    'name',
                                    'meta_title',
                                    'meta_description',
                                    'meta_keywords',
                                    'url_key'
                            );

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
