<?php

class CSVToJson
{
    // Properties
    public $datas = [];
    public $handle;

    // Constructor will store CSV file data in a variable
    function __construct($filename)
    {
        $csvs = [];
        $column_names = [];
        $this->handle = fopen($filename, "r");
        if ($this->handle !== FALSE) {
            // Fecth all the data of CSV file
            while(!feof($this->handle)) {
               $csvs[] = fgetcsv($this->handle);
            }

            // Fecth all the column names of CSV file
            foreach ($csvs[0] as $single_csv) {
                $column_names[] = $single_csv;
            }

            // Store all the data to datas variable except the column names
            foreach ($csvs as $key => $csv) {
                if ($key === 0) {
                    continue;
                }

                foreach ($column_names as $column_key => $column_name) {
                    $this->datas[$key-1][$column_name] = $csv[$column_key];
                }
            }
        }
    }

    // This method will convert the csv data to json format
    function converCSVToJson()
    {
        $products = $csv_json = [];
        foreach ($this->datas as $value) {
            if ($value['Action'] == "Product") {
                $csv_json[] = $products;
                $products = [];

                $products['id'] = $value['Id'];
                $products['mpn'] = $value['MPN'];
                $products['sku'] = $value['SKU'];
                $products['name'] = $value['Name'];
                $products['slug'] = $value['Slug'];
                $products['price'] = $value['Price'];
                $products['stock'] = $value['Stock'];
                $products['active'] = true;
                $products['images'] = [];
                $products['barcode'] = $value['Barcode'];
                $products['on_sale'] = ($value['On Sale'] == '') ? false : true;
                $products['children'] = [];
                $products['meta_title'] = $value['Meta Title'];
                $products['product_category_id'] = $value['Category Path'];
            }
            
            // Create an array of images inside the product document
            if ($value['Action'] == "Image") {
                if (isset($products['images']) && sizeof($products['images']) > 0) {
                    array_push($products['images'], [
                        'id' => $value['Id'],
                        'alt' => $value['Name'],
                        'source' => $value['Image URL'],
                    ]);
                } else {
                    $products['images'][] = [
                        'id' => $value['Id'],
                        'alt' => $value['Name'],
                        'source' => $value['Image URL'],
                    ];
                }
            }
            
            // Create an array of all the children products inside the product document
            if ($value['Action'] == "Variant") {
                if ($value['Action Type'] == "Product") {
                    if (isset($products['children']) && sizeof($products['children']) > 0) {
                        array_push($products['children'], [
                            'sku' => $value['SKU'],
                            'slug' => $value['Slug'],
                            'price' => $value['Price'],
                            'stock' => $value['Stock'],
                            'on_sale' => ($value['On Sale'] == '') ? false : true,
                            'attributes' => [],
                            'sale_price' => $value['Sale Price'],
                            'track_stock' => ($value['Track Stock'] == '') ? false : true,
                        ]);
                    } else {
                        $products['children'][] = [
                            'sku' => $value['SKU'],
                            'slug' => $value['Slug'],
                            'price' => $value['Price'],
                            'stock' => $value['Stock'],
                            'on_sale' => ($value['On Sale'] == '') ? false : true,
                            'attributes' => [],
                            'sale_price' => $value['Sale Price'],
                            'track_stock' => ($value['Track Stock'] == '') ? false : true,
                        ];
                    }
                }

                if ($value['Action Type'] == "Attribute") {
                    $lastKey = count($products['children']) - 1;

                    if ($value['Name'] == "Size") {
                        $products['children'][$lastKey]['attributes']['Size'] = $value['Attribute Value'];
                    }
                    
                    if ($value['Name'] == "Colour") {
                        $products['children'][$lastKey]['attributes']['Colour'] = $value['Attribute Value'];
                    }
                }
            }
        }

        fclose($this->handle);
        $csv_json[] = $products;
        // Remove first element of the array
        array_shift($csv_json);
        // Encoding the array to JSON
        return json_encode($csv_json);        
    }
}

$fetchJsonData = new CSVToJson("products.csv");
echo $fetchJsonData->converCSVToJson();