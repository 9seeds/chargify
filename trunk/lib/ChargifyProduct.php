<?php

//Reference Documentation: http://support.chargify.com/faqs/api/api-products

class ChargifyProduct extends ChargifyConnector
{
  private $price_in_cents;
  private $name;
  private $handle;
  private $product_family = array('name' => NULL, 'handle' => NULL, 'accounting_code' => NULL);
  private $accounting_code;
  private $interval_unit;
  private $interval;
  private $description;

  public function __construct($in)
  {
	if(!is_object($in))
	{	  
		$product_xml_node = new SimpleXMLElement($product_xml_node);
		//Load object dynamically and convert SimpleXMLElements into strings
		foreach($product_xml_node as $key => $element) 
		{
			if (count($element)) 
			{
				foreach($element as $childname => $child) 
				{
					$this->{$key}[$childname] = (string)$child;
				}
			}
			else 
			{
				$this->$key = (string)$element;
			}
		}
	}
	else
	{
		$product_xml_node = (array)$in;
		foreach($product_xml_node as $key => $element) 
		{
			if (is_object($element)) 
			{
				foreach((array)$element as $childname => $child) 
				{
					$this->{$key}[$childname] = (string)$child;
				}
			}
			else 
			{
				$this->$key = (string)$element;
			}
		}
	}
  }
  
  
  /* Getters */
  
  public function getPriceInCents() { return $this->price_in_cents; }
  
  public function getPriceInDollars() { return number_format($this->price_in_cents / 100, 0); }
  
  public function getName() { return $this->name; }
  
  public function getHandle() { return $this->handle; }
  
  public function getProductFamily() { return $this->product_family; }
  
  public function getAccountCode() { return $this->accounting_code; }
  
  public function getIntervalUnit() { return $this->interval_unit; }
  
  public function getInterval() { return $this->interval; }

  public function getDescription() {return $this->description; }

}
