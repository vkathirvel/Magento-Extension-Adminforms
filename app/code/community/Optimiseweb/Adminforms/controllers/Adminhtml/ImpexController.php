<?php

/**
 * Optimiseweb Adminforms Adminhtml Impex Controller
 *
 * @package     Optimiseweb_Adminforms
 * @author      Kathir Vel (sid@optimiseweb.co.uk)
 * @copyright   Copyright (c) 2014 Optimise Web
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Optimiseweb_Adminforms_Adminhtml_ImpexController extends Mage_Adminhtml_Controller_Action
{

    /**
     *
     */
    public function preDispatch()
    {
        parent::preDispatch();
        ini_set('max_execution_time', 0);
        ini_set('default_socket_timeout', 1200);
    }

    /**
     *
     */
    public function indexAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('adminhtml/template', 'impex', array('template' => 'optimiseweb/adminforms/impex/master.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    /**
     *
     * @param type $storeId
     * @param type $attributesToSelect
     * @return type
     */
    protected function loadProductCollection($storeId = 1, $attributesToSelect = '*')
    {
        /* Get the products */
        Mage::app()->setCurrentStore($storeId);
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addStoreFilter();
        $collection->addPriceData();
        $collection->addAttributeToSelect($attributesToSelect);
        return $collection;
    }

    /**
     *
     * @param type $cells
     * @return boolean
     */
    protected function csvRowFromArray($cells)
    {
        $i = count($cells);
        $j = 1;
        if ($i > 0) {
            $row = '';
            foreach ($cells as $cell) {
                $row .= '"' . Mage::helper('adminforms')->fixQuotes($cell) . '"';
                if ($i == $j) {
                    $row .= "\n";
                } else {
                    $row .= ',';
                }
                $j++;
            }
            return $row;
        }
        return FALSE;
    }

    /**
     *
     * @param type $exportFile
     * @param type $fileContents
     */
    protected function downloadCsv($exportFile, $fileContents)
    {
        header("Content-type: text/x-csv");
        header("Content-Disposition: attachment;filename = $exportFile");
        echo $fileContents;
        exit();
    }

    protected function uploadCsvGetDataset($importFile)
    {
        /* Storage path for the CSV */
        $path = Mage::getBaseDir('var') . DS . 'import' . DS;
        $uploader = new Varien_File_Uploader('importfile');
        $uploader->setAllowedExtensions(array('csv'));
        $uploader->setAllowRenameFiles(false);
        $uploader->setFilesDispersion(false);
        $uploader->save($path, $importFile);
        /* Create an array from the CSV file */
        $newFile = fopen($path . $importFile, 'r');
        /* Gets the headers row */
        $newDetails = array();
        $linesHeaders = fgetcsv($newFile);
        while (($newLines = fgetcsv($newFile)) !== FALSE) {
            /* Merge headers as keys */
            $newDetails[] = array_combine($linesHeaders, $newLines);
        }
        fclose($newFile);

        return $newDetails;
    }

    /**
     *
     * @param type $newDetail
     * @return type
     */
    protected function prepNewDetail($newDetail)
    {
        $resultArray = array();
        foreach ($newDetail as $key => $value) {
            if (TRIM($value) == "") {
                $resultArray[$key] = NULL;
            } else {
                $resultArray[$key] = $value;
            }
        }
        return $resultArray;
    }

    /**
     * 
     * @param type $model
     * @param type $data
     * @param type $attributes
     * @param boolean $saveModel
     * @return boolean
     */
    protected function modelAttributeCheckAndSave($model, $data = array(), $attributes = array(), $saveModel = FALSE)
    {
        if (count($data) > 0) {
            if (count($attributes) == 0) {
                $attributes = array_keys($data);
            }
            /* Loop through the attributes */
            foreach ($attributes as $attribute) {
                if (!is_numeric($attribute) AND array_key_exists($attribute, $data) AND ( $model->getData($attribute) !== $data[$attribute])) {
                    if (Mage::helper('adminforms')->nullToEmpty($model->getData($attribute)) !== Mage::helper('adminforms')->nullToEmpty($data[$attribute])) {
                        $model->setData($attribute, Mage::helper('adminforms')->nullToEmpty($data[$attribute]));
                        $saveModel = TRUE;
                    }
                }
            }
        }
        return $saveModel;
    }

    /**
     *
     * @param type $successCounter
     * @param type $errorCounter
     * @param type $rowCounter
     */
    protected function setSuccessSummaryMessage($successCounter, $errorCounter, $rowCounter)
    {
        if ($successCounter == 0) {
            $successMessage = 'Nothing to import.';
        } elseif ($errorCounter == 0) {
            $successMessage = 'Import Successful. Imported ' . $successCounter . ' rows.';
        } elseif (($successCounter > 0) AND ( $errorCounter > 0)) {
            $successMessage = 'Import Successful. Imported ' . $successCounter . ' rows. ' . $errorCounter . ' errors encountered.';
        } else {
            $successMessage = 'Import Successful. ' . $errorCounter . ' errors encountered.';
        }
        Mage::getSingleton('adminhtml/session')->addSuccess($successMessage);
    }

    /**
     * TODO : Log Email Function
     */
    protected function logEmail()
    {
        /* Email */
        $logEmail = Mage::helper('adminforms')->logEmail();
        if ($logEmail['enable']) {
            $emailVariables = array('content' => $log);
            Mage::helper('emailer')->sendemail($logEmail['to_name'], $logEmail['to_email'], $logEmail['from_name'], $logEmail['from_email'], 'SEO Import Log Email', $emailVariables);
            //Mage::helper('emailer')->sendEmails($emailSenderName, $emailSenderEmail, $emailRecipientName, $emailRecipientEmail, $emailReplyTo = NULL, $emailCC = NULL, $emailBCC = NULL, $emailSubject = NULL, $emailVariables, $emailAttachment = NULL, $emailTemplate);
        }
    }

}
