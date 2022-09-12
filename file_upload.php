<?php
header('Content-Type: application/json');
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$ProductsJSONFile = "uploads/products.json";
$CustomersJSONFile = "uploads/customers.json";

// Check if file already exists
// if (file_exists($target_file)) {
//   echo "Sorry, file already exists.";
//   $uploadOk = 0;
// }

// Check file size
if ($_FILES["fileToUpload"]["size"] > 500000) {
  echo "Sorry, your file is too large.";
  $uploadOk = 0;
}

// Allow certain file formats
if($imageFileType != "json" ) {
  echo "Sorry, only JSON files are allowed.";
  $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
  echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} 
else {
  if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    echo "The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.";
  } else {
    echo "Sorry, there was an error uploading your file.";
  }
}

// Read the Order JSON file
$json = file_get_contents($target_file);

// Decode the Order JSON file
$order_data = json_decode($json,true);

// Read the Product JSON file
$prod_json = file_get_contents($ProductsJSONFile);

// Decode the Product JSON file
$product_data = json_decode($prod_json,true);

// Read the Customer JSON file
$cust_json = file_get_contents($CustomersJSONFile);

// Decode the Customer JSON file
$customer_data = json_decode($cust_json,true);

// - A customer who has already bought for over € 1000, gets a discount of 10% on the whole order.
// - For every product of category "Switches" (id 2), when you buy five, you get a sixth for free.
// - If you buy two or more products of category "Tools" (id 1), you get a 20% discount on the cheapest product.
    
$cat1_quantity = 0;
$cat2 = 0;
$cat1 = 0;
$total = 0;
$total2 = 0;
$i = 0;
$category1_array = array();
$min_array = array();

//Checking if there is any order in Category ID 1 and 2
    foreach ($order_data['items'] as $orderkey => $order_val) {
        foreach($product_data as $productkey => $product_val)
        {
            if($order_val['product-id'] == $product_val['id'])
            {
                if($product_val['category'] == 2)
                {
                  $cat2++; 
                }
                    
                if($product_val['category'] == 1)
                {
                    $cat1++;
                    $cat1_quantity+=$order_val['quantity'];
                    $category1_array[$i] = $order_data['items'][$orderkey];  //Creating array for all the items in this category
                    $i++;
                }
            }
        } 
      }

    //Getting the Item from the order list for Category ID 1 and having the min price in the same category
    if($cat1 >= 2 || $cat1_quantity >= 2)
      {
        $prices = array_column($category1_array, 'unit-price');
        $min_array = $category1_array[array_search(min($prices = array_column($category1_array, 'unit-price')), $prices)];
      }

    // Applying discounts   
    foreach($order_data['items'] as $orderkey => $order_val)
    {
      //if there is any applicable for Category ID 2
      if($cat2 > 0)
      {
        if($order_val['quantity']>5 )
        {
          $discounted_items = floor($order_val['quantity']/6); 
          $order_data['items'][$orderkey]['total'] = ($order_val['quantity'] - $discounted_items)*$order_val['unit-price'];
          $order_data['items'][$orderkey]['discounted-quantity-cat2'] = $discounted_items;
          $order_data['items'][$orderkey]['discounted-price-cat2'] = $discounted_items*$order_val['unit-price'];
        }
      }
      $total = $order_data['items'][$orderkey]['total'] + $total;

      //if there is any applicable for Category ID 1
      if(!empty($min_array))
      {
        if($min_array['product-id'] == $order_val['product-id'])
        {
          $cat1_discounted_price = $order_data['items'][$orderkey]['total'] * 0.20;
          $order_data['items'][$orderkey]['total'] -=  $cat1_discounted_price;
          $order_data['items'][$orderkey]['discounted-price-cat1'] = $cat1_discounted_price;
        }       
        $total2+=$order_data['items'][$orderkey]['total']; 
      }   
    }

    $order_data['total'] = $total;
    $order_data['total'] = $total2;

    //Applying discount,  if the total goes beyond 1000Euros
    if($order_data['total'] > '1000')
    {
      $discount_price = $order_data['total']*0.10;
      $order_data['total'] = $order_data['total'] - $discount_price ;
      $order_data['10_percent_discount_applicable'] = $discount_price;
    }

    //Creating JSON file.
    $json = json_encode($order_data);
    file_put_contents("discounted-order.json",$json );

    //Displaying result 
    echo '<pre>';
    print_r($order_data);
    echo '</pre>';
    
    
    
    
?>