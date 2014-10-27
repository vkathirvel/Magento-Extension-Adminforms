<?php

/**
 * Optimiseweb Adminforms Adminhtml Impex Fishpigsplashpages Controller
 *
 * @package     Optimiseweb_Adminforms
 * @author      Kathir Vel (sid@optimiseweb.co.uk)
 * @copyright   Copyright (c) 2014 Optimise Web
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once Mage::getModuleDir('controllers', 'Optimiseweb_Adminforms') . DS . 'Adminhtml' . DS . 'ImpexController.php';

class Optimiseweb_Adminforms_Adminhtml_Impex_FishpigsplashpagesController extends Optimiseweb_Adminforms_Adminhtml_ImpexController
{

    protected $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
    protected $exportFile = 'export_seo_fishpig_splash_pages.csv';
    protected $importFile = 'import_seo_fishpig_splash_pages.csv';
    protected $rowCounter = 0;
    protected $successCounter = 0;
    protected $errorCounter = 0;

    /**
     * Export Fishpig Splash Pages SEO Data
     */
    public function exportfishpigsplashpagesAction()
    {
        /* Get the products */
        $storeId = $this->getRequest()->getParam('store_id');
        if (!empty($storeId)) {
            $this->storeId = $storeId;
        }
        Mage::app()->setCurrentStore($this->storeId);
        $collection = Mage::getResourceModel('attributeSplash/page_collection');

        /* Prepare CSV headers */
        $columnHeaders = array(
                'page_id',
                'display_name',
                'page_title',
                'meta_description',
                'meta_keywords',
                'url_key',
        );
        $fileContents = $this->csvRowFromArray($columnHeaders);

        /* Loop through the product collection */
        foreach ($collection as $page) {
            $pageRowValues = array(
                    $page->getData('page_id'),
                    $page->getData('display_name'),
                    $page->getData('page_title'),
                    $page->getData('meta_description'),
                    $page->getData('meta_keywords'),
                    $page->getData('url_key'),
            );
            $fileContents .= $this->csvRowFromArray($pageRowValues);
        }

        /* Export and Dump the File */
        $this->downloadCsv($this->exportFile, $fileContents);
    }

    /**
     * Import Fishpig Splash Pages SEO Data
     */
    public function importfishpigsplashpagesAction()
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

                        $model = Mage::getModel('attributeSplash/page')->load($newDetail['page_id']);

                        if ($model->getPageId()) {
                            /* Attributes to check and update */
                            $attributes = array(
                                    'display_name',
                                    'page_title',
                                    'meta_description',
                                    'meta_keywords',
                                    'url_key',
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
