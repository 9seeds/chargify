<?php

/******************************************************************************************

  Christopher Lewis, 2010

  Reference Documentation: http://support.chargify.com/faqs/api/api-authentication

******************************************************************************************/

class ChargifyConnector
{
  protected $api_key;
  protected $test_api_key;
  protected $domain;
  protected $test_domain;
  
  protected $active_api_key;
  protected $active_domain;
  protected $test_mode;
  
  public function __construct($opt)
  {
	  $this->test_mode = $opt["test_mode"];
	  $this->api_key = $opt["api_key"];
	  $this->test_api_key = $opt["test_api_key"];
	  $this->domain = $opt["domain"]; 
	  $this->test_domain = $opt["test_domain"]; 

    if($opt["test_mode"])
    {
      $this->active_api_key = $this->test_api_key;
      $this->active_domain  = $this->test_domain;
    }
    else
    {
      $this->active_api_key = $this->api_key;
      $this->active_domain  = $this->domain;
    }
  }
  
  public function retrieveAllCustomersXML($page_num = 1)
  {
    return $this->sendRequest('/customers.xml?page=' . $page_num);
  }
  
  public function retrieveProductXMLByID($id)
  {
    	return $this->sendRequest('/products/' . $id . '.xml');
	}
  
  public function retrieveCustomerXMLByID($id)
  {
    return $this->sendRequest('/customers/' . $id . '.xml');
  }
  
  public function retrieveCustomerXMLByReference($ref)
  {
    return $this->sendRequest('/customers/lookup.xml?reference=' . $ref);
  }
  
  public function retrieveSubscriptionsXMLByCustomerID($id)
  {
    return $this->sendRequest('/customers/' . $id . '/subscriptions.xml');
  }	
  public function retrieveSubscriptionsXMLBySubscriptionID($id)
  {
    return $this->sendRequest('/subscriptions/' . $id . '.xml');
  }
  
  public function retrieveAllProductsXML()
  {
    return $this->sendRequest('/products.xml');
  }
  public function retrieveAllWebhooksXML()
  {
    return $this->sendRequest('/webhooks.xml');
  }


  /*
     Example post xml:     
 
     <?xml version="1.0" encoding="UTF-8"?>
      <subscription>
        <product_handle>' . $product_id . '</product_handle>
        <customer_attributes>
          <first_name>first</first_name>
          <last_name>last</last_name>
          <email>email@email.com</email>
        </customer_attributes>
        <credit_card_attributes>
          <first_name>first</first_name>
          <last_name>last</last_name>
          <billing_address>1 Infinite Loop</billing_address>
          <billing_city>Somewhere</billing_city>
          <billing_state>CA</billing_state>
          <billing_zip>12345</billing_zip>
          <billing_country>USA</billing_country>
          <full_number>41111111111111111</full_number>
          <expiration_month>11</expiration_month>
          <expiration_year>2012</expiration_year>
        </credit_card_attributes>
      </subscription>
  */
  /**
   * @return SimpleXMLElement|ChargifySubscription
   */
  public function createCustomerAndSubscription($post_xml)
  {
    $xml = $this->sendRequest('/subscriptions.xml', $post_xml);

    $tree = new SimpleXMLElement($xml);

    if(isset($tree->error)) { return $tree; }
    else { $subscription = new ChargifySubscription($tree); }
    
    return $subscription;
  }
  
  public function getAllCustomers()
  {
    $xml = $this->retrieveAllCustomersXML();
    
    $all_customers = new SimpleXMLElement($xml);
   
    $customer_objects = array();
    
    foreach($all_customers as $customer)
    {
      $temp_customer = new ChargifyCustomer($customer);
      array_push($customer_objects, $temp_customer);
    }
    
    return $customer_objects;
  }
  
  /**
   * @return ChargifyCustomer
   */
  public function getCustomerByID($id)
  {
    $xml = $this->retrieveCustomerXMLByID($id);
    
    $customer_xml_node = new SimpleXMLElement($xml);
    
    $customer = new ChargifyCustomer($customer_xml_node);
    
    return $customer;
  }
  
  /**
   * @return ChargifyCustomer
   */
  public function getCustomerByReference($ref)
  {
    $xml = $this->retrieveCustomerXMLByReference($ref);

    $customer_xml_node = new SimpleXMLElement($xml);
    
    $customer = new ChargifyCustomer($customer_xml_node);
        
    return $customer;
  }

  public function getProductByID($id)
  {
	  $xml = $this->retrieveProductXMLByID($id);
	  $product_xml_node = new SimpleXMLElement($xml);
	  $product = new ChargifyProduct($product_xml_node);
	  return $product;
  }

  public function getSubscriptionsByCustomerID($id)
  {
    $xml = $this->retrieveSubscriptionsXMLByCustomerID($id);
    
    $subscriptions = new SimpleXMLElement($xml);
    
    $subscription_objects = array();
    
    foreach($subscriptions as $subscription)
    {
      $temp_sub = new ChargifySubscription($subscription);
      
      array_push($subscription_objects, $temp_sub);
    }
    
    return $subscription_objects;
  }
  public function getSubscriptionsBySubscriptionID($id)
  {
		$xml = $this->retrieveSubscriptionsXMLBySubscriptionID($id);
		
		$subscription = new SimpleXMLElement($xml);
		
		$subscription_object = new ChargifySubscription($subscription);
			
		return $subscription_object;
  } 
  public function getAllProducts()
  {
    $xml = $this->retrieveAllProductsXML();
	
	$all_products = new SimpleXMLElement($xml);
    
	$product_objects = array();
    
    foreach($all_products as $product)
    {
      $temp_product = new ChargifyProduct($product);
      array_push($product_objects, $temp_product);
    }
    
    return $product_objects;
  }
  public function getAllWebhooks()
  {
    $xml = $this->retrieveAllWebhooksXML();
	$all_webhooks = new SimpleXMLElement($xml);
   
    return $all_webhooks;
  }
  
  /**
   * @return ChargifyCustomer
   */
  public function createCustomer($post_xml) {
    $xml = $this->sendRequest('/customers.xml', $post_xml);
    
    $customer_xml_node = new SimpleXMLElement($xml);
    
    $customer = new ChargifyCustomer($customer_xml_node);
    
    return $customer;
  }


  public function cancelSubscription($id) {
    return $this->sendRequest('/subscriptions/' . $id . '.xml',NULL,'delete');
  }

  public function updateProduct($id,$data) {
	$post_xml = '
		<?xml version="1.0" encoding="UTF-8"?>
		<product>';
	foreach($data as $k=>$v)
		$post_xml .= '<'.$k.'>'.$v.'</'.$k.'>';
	$post_xml .= '</product>';

	$xml = $this->sendRequest('/products/'.$id.'.xml',trim($post_xml),'put');
    $pxml = new SimpleXMLElement($xml);
	$product = new ChargifyProduct($pxml);
	return $product;
  }
  
  protected function sendRequest($uri, $post_xml = null,$method = null) {    
	$apiUrl = "https://{$this->active_domain}.chargify.com{$uri}";
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_USERPWD,$this->active_api_key.':x');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($ch, CURLOPT_HEADER , 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	if($method == 'put')
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	}
	if($post_xml)
	{
    	curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_xml);
	    curl_setopt($ch, CURLOPT_HTTPHEADER , array('Content-Type: application/xml'));
	}
	if($method == 'delete')
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	}
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $xml = curl_exec($ch);
	
	curl_close($ch);
	
	libxml_use_internal_errors(true);
	$sxe = simplexml_load_string($xml);
	if (!$sxe) { $xml = '<error>'.$xml.'</error>'; }

	return $xml;
  }
}
