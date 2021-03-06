<?php

/**
 * Created by PhpStorm.
 * User: zeke
 * Date: 3/1/16
 * Time: 12:34 PM
 */
class Gearx_FastApi_Model_Product_Api extends Mage_Catalog_Model_Api_Resource
{

//    $products = array( 
//        '5674-MD-BLUE' => array(
//            'special_price' => 39.95,
//            'price' => 49.95,
//            'qty' => 13,
//            'upc' => '543749281654',
//            'location' => 'XW4E2',
//        ),
//        '5674-SM-BLUE' => array(
//            'special_price' => 39.95,
//            'price' => 49.95,
//            'qty' => 8,
//            'upc' => '543749281653',
//            'location' => 'XW4E2',
//        ),
//        //etc
//    );
    public function update($products, $field_map_code = false)
    {
        if (!is_array($products) || count($products) == 0 ) {
            $this->_fault('bad_param');
        }
        $request = Mage::getSingleton('gxapi/request');
        try {
            $request->setFieldMap($field_map_code);
        } catch (Exception $e) {
            $this->_fault('bad_param', $e->getMessage());
        }
        foreach ($products as $sku => $fields) {
            try {
                $product = new Gearx_FastApi_Model_Product($sku);
                foreach ($fields as $code => $value) {
                    $mapped_code = $request->getMappedField($code);
                    if ($mapped_code) {
                        $product->updateField($mapped_code, $value);
                    }
                }
            } catch (Exception $e) {
                $request->addError($e->getMessage());
            }
        }
        if (Mage::getStoreConfig('api/gearx/reindex')) {
            $request->reindexUpdatedProducts();
        }
        return $request->getResponse();
    }
    
    public function checkSkus($skus)
    {
        if (is_array($skus) && count($skus) > 0 ) {
            $database = Mage::getSingleton('gxapi/database');
            $bind_set = str_repeat('?,', count($skus) - 1) . '?';
            $cpe = $database->table('catalog_product_entity');
            $query = "SELECT sku, type_id FROM $cpe WHERE sku IN ($bind_set) ;";
            $results = $database->fetchAll($query, $skus);
            
            $response = array_fill_keys($skus, false);
            foreach ($results as $result) {
                $sku = $result['sku'];
                if (array_key_exists($sku, $response)) {
                    $response[$sku] = $result['type_id'];
                }
            }
            return $response;
        } else {
            $this->_fault('bad_param');
        }

    }

}
