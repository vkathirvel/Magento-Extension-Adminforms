<?php

/**
 * Optimiseweb Adminforms Data Helper
 *
 * @package     Optimiseweb_Adminforms
 * @author      Kathir Vel (sid@optimiseweb.co.uk)
 * @copyright   Copyright (c) 2014 Optimise Web
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Optimiseweb_Adminforms_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * getProductDropdownAttributeValue
     *
     * @param type $product
     * @param type $attributeName
     * @return type
     */
    public function getProductDropdownAttributeValue($product, $attributeName)
    {
        $dropdownAttributeObj = $product->getResource()->getAttribute($attributeName);
        if ($dropdownAttributeObj->usesSource()) {
            $dropdown_option_label = $dropdownAttributeObj->getSource()->getOptionText($product->getData($attributeName));
        }

        return $dropdown_option_label;
    }

    /**
     * Fix Double Quotes
     *
     * @param type $value
     */
    public function fixQuotes($value)
    {
        return str_replace("\"", "\"\"", TRIM($value));
    }

    /**
     * Convert Null to Empty
     *
     * @param string $value
     * @return string
     */
    public function nullToEmpty($value)
    {
        if ($value == NULL) {
            $value = '';
        }
        return $value;
    }

    /**
     *
     * @return boolean
     */
    public function logEmail()
    {
        $logEmail['enable'] = FALSE;
        return $logEmail;
    }

}
