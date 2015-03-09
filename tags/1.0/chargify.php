<?php
/*
Plugin Name: Chargify Wordpress Plugin
Plugin URI: http://subscriptiontools.com/
Description: Manage subscriptions to WordPress using the Chargify API
Author: Subscription Tools - Programming by 9seeds
Version: 1.0
Author URI: http://subscriptiontools.com/
 */
$base = dirname(__FILE__);

include($base.'/lib/ChargifyConnector.php');
include($base.'/lib/ChargifyCreditCard.php');
include($base.'/lib/ChargifyCustomer.php');
include($base.'/lib/ChargifyProduct.php');
include($base.'/lib/ChargifySubscription.php');

add_shortcode('chargify', array('chargify','subscriptionListShortCode'));
add_action('admin_menu',array('chargify','control'));
add_filter('the_posts',array('chargify','checkAccess'));
add_action('admin_menu', array('chargify','createMetaAccessBox'));  
add_action('save_post', array('chargify','metaAccessBoxSave'));  
add_filter('init', array('chargify','subscriptionRedirect'));  
add_filter('the_content', array('chargify','subscriptionPost'));  
add_action('show_user_profile', array('chargify','userActions'));
add_action('edit_user_profile', array('chargify','userActions'));
add_action('profile_update', array('chargify','userActionsUpdate'));
register_activation_hook(__FILE__,array("chargify","activate"));
register_deactivation_hook(__FILE__,array("chargify","deactivate"));

class chargify
{
	function manualTrim($text)
	{
        $text = strip_shortcodes( $text );

        $text = apply_filters('the_content', $text);
        $text = str_replace(']]>', ']]&gt;', $text);
        $text = strip_tags($text);
        $excerpt_length = apply_filters('excerpt_length', 55);
        $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
        $words = explode(' ', $text, $excerpt_length + 1);
        if (count($words) > $excerpt_length) {
            array_pop($words);
            $text = implode(' ', $words);
            $text = $text . $excerpt_more;
        }
		return $text;
	}
	function checkAccess($posts)
	{
	    $user = wp_get_current_user();
		if($user->roles[0] == 'administrator')
		{
			return $posts;
		}		
		
		foreach($posts as $k => $post)
		{
			$chargify = get_option('chargify');
			$d = get_post_meta($post->ID, 'chargify_access', true); 
			$u = wp_get_current_user();
			if(is_array($d["levels"]))
			{
				if(!in_array($u->chargify_level,$d["levels"]))
				{
					switch($chargify["chargifyNoAccessAction"])
					{
						case 'excerpt':
							$post->post_content = strlen(trim($post->post_excerpt)) ? $post->post_excerpt : chargify::manualTrim($post->post_content);
							break;
						default:
							$post->post_content = $chargify["chargifyDefaultNoAccess"]; 
					}
				}
				$post->post_content .= '<br><br>Please log in or <a href="'.$chargify["chargifySignupLink"].'"><strong>Subscribe</strong></a> to see the Webcasts page. <p><img src="http://www.deprogramprogram.com/wp-content/uploads/2009/11/Northern-Israel1.jpg" alt="Northern Israel" title="Northern Israel" width="550" height="413" class="aligncenter size-full wp-image-504" /></p>';
			}
			 
			$posts[$k] = $post;
		}
		return $posts;
	}
	function products()
	{
		$d = get_option('chargify');
		$opt = array("api_key" => $d["chargifyApiKey"],"test_api_key" => $d["chargifyTestApiKey"],"domain" => $d["chargifyDomain"],"test_domain" => $d["chargifyTestDomain"],"test_mode"=>($d["chargifyMode"] == 'test'? TRUE : FALSE));	
		$connector = new ChargifyConnector($opt);
		$products = $connector->getAllProducts();

		return $products;
	}

	function subscriptionListShortCode($atts)
	{
		extract(shortcode_atts(array('accountingcodes' => '20,22'), $atts));
		$filteraccountingcodes = array();
		if ($accountingcodes != '') {
			$acs = explode(',', $accountingcodes);
			for($i = 0; $i < count($acs); $i++) {
				$filteraccountingcodes[$acs[$i]] = true;
			}
		}
		$d = get_option("chargify");
		if($d["chargifySignupType"] == 'api')
		{	
			$monthDrop = '<select style="width:50px" name="chargifySignupExpMo">';
			for($i=1; $i<13; $i++)
			{
				$monthDrop .= '<option value="'.$i.'" '.($_POST["chargifySignupExpMo"] == $i ? "selected" : "").'>'.$i.'</option>';
			}
			$monthDrop .= '</select>';
			$yearDrop = '<select style="width:70px" name="chargifySignupExpYr">';
			for($i=(int)date("Y"); $i < (int)date("Y",strtotime("+10 years")); $i++)
			{
				$yearDrop .= '<option value="'.$i.'" '.($_POST["chargifySignupExpYr"] == $i ? "selected" : "").'>'.$i.'</option>';
			}
			$yearDrop .= '</select>';
			
			$products = chargify::products();
			$form ='<form name="chargifySignupForm" method="post" action="">
			<input type="hidden" name="chargify_signupcc_noncename" id="chargify_signupcc_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />
			<input type="hidden" name="submit" value="">	
			<table>
				<tr>
					<th colspan="2"><p><strong>Subscriber Information</strong></p></th>
				</tr>
				<tr>
					<td>First Name</td>
					<td><input type="text" name="chargifySignupFirst" value="'.$_POST["chargifySignupFirst"].'"></td>
				</tr>
				<tr>
					<td>Last Name</td>
					<td><input type="text" name="chargifySignupLast" value="'.$_POST["chargifySignupLast"].'"></td>
				</tr>
				<tr>
					<td>Email</td>
					<td><input type="text" name="chargifySignupEmail" value="'.$_POST["chargifySignupEmail"].'"></td>
				</tr>
				<tr>
					<th colspan="2">Payment Info</th>
				</tr>
				<tr>
					<td>Billing First Name</td>
					<td><input type="text" name="chargifySignupBillFirst" value="'.$_POST["chargifySignupBillFirst"].'"></td>
				</tr>
				<tr>
					<td>Billing Last Name</td>
					<td><input type="text" name="chargifySignupBillLast" value="'.$_POST["chargifySignupBillLast"].'"></td>
				</tr>
				<tr>
					<td>Billing Address</td>
					<td><input type="text" name="chargifySignupBillAddress" value="'.$_POST["chargifySignupBillAddress"].'"></td>
				</tr>
				<tr>
					<td>Billing City</td>
					<td><input type="text" name="chargifySignupBillCity" value="'.$_POST["chargifySignupBillCity"].'"></td>
				</tr>
				<tr>
					<td>Billing State</td>
					<td><input type="text" name="chargifySignupBillState" value="'.$_POST["chargifySignupBillState"].'"></td>
				</tr>
				<tr>
					<td>Billing Zip Code</td>
					<td><input type="text" name="chargifySignupBillZip" value="'.$_POST["chargifySignupBillZip"].'"></td>
				</tr>
				<tr>
					<td>Billing Country</td>
					<td><input type="text" name="chargifySignupBillCountry" value="'.$_POST["chargifySignupBillCountry"].'"></td>
				</tr>
				<tr>
					<td>Credit Card Number</td>
					<td><input type="text" name="chargifySignupBillCc" value="'.$_POST["chargifySignupBillCc"].'"></td>
				</tr>
				<tr>
					<td>Credit Card Expiration</td>
					<td>Month: '.$monthDrop.'<br>Year:'.$yearDrop.'</td>
				</tr>	
				';
			foreach($products as $p)
			{
				if (isset($filteraccountingcodes[$p->getAccountCode()]) && $filteraccountingcodes[$p->getAccountCode()]) {
					$form .= '<tr>';
					$form .= '<td><div align="center"><strong>'.$p->getName().'</strong><br>$'.$p->getPriceInDollars().' '.($p->getInterval() == 1 ? 'each '.$p->getIntervalUnit() : 'every '.$p->getInterval().' '.$p->getIntervalUnit().'s').'<br>'.$p->description.'</div></td>';
					$form .= '<td><input onclick="javascript:document.chargifySignupForm.submit.value=\''.$p->getHandle().'\';" name="submit'.$p->getHandle().'" type="submit" value="'.$p->getName().'"></td>';
					$form .= '</tr>';
				}
			}

			$form .= '</table>
			</form>';
		}
		else
		{
			$form ='<form name="chargifySignupForm" method="post" action="">
			<input type="hidden" name="chargify_signup_noncename" id="chargify_signupcc_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />
			<input type="hidden" name="submit" value="">	
			<table>
				<tr>
					<th colspan="2"><p><strong>Subscriber Information</strong></p></th>
				</tr>
				<tr>
					<td>First Name</td>
					<td><input type="text" name="chargifySignupFirst" value="'.$_POST["chargifySignupFirst"].'"></td>
				</tr>
				<tr>
					<td>Last Name</td>
					<td><input type="text" name="chargifySignupLast" value="'.$_POST["chargifySignupLast"].'"></td>
				</tr>
				<tr>
					<td>Email</td>
					<td><input type="text" name="chargifySignupEmail" value="'.$_POST["chargifySignupEmail"].'"></td>
				</tr>
				<tr>
					<th colspan="2"><p><strong>Subscription Level</strong></p></th>
				</tr>
				';
			
			$products = chargify::products();
			foreach($products as $p)
			{
				if (isset($filteraccountingcodes[$p->getAccountCode()]) && $filteraccountingcodes[$p->getAccountCode()]) {
					$form .= '<tr>';
					$form .= '<td><div align="center"><strong><p>'.$p->getName().'</strong><br>$'.$p->getPriceInDollars().' '.($p->getInterval() == 1 ? 'each '.$p->getIntervalUnit() : 'every '.$p->getInterval().' '.$p->getIntervalUnit().'s').'<br>'.$p->description.'</p></div></td>';
					$form .= '<td><p><input onclick="javascript:document.chargifySignupForm.submit.value=\''.$p->id.'\';" name="submit'.$p->getHandle().'" type="submit" value="'.$p->getName().'"></p></td>';
					$form .= '</tr>';
				}
			}
			$form .= '</table>';
			$form .= '</form>';


		}
		return $form;
	}
	function userActionsUpdate($user_id)
	{
		if($_POST["chargifyCancelSubscription"])
		{
			$d = get_option('chargify');
			$opt = array("api_key" => $d["chargifyApiKey"],"test_api_key" => $d["chargifyTestApiKey"],"domain" => $d["chargifyDomain"],"test_domain" => $d["chargifyTestDomain"],"test_mode"=>($d["chargifyMode"] == 'test'? TRUE : FALSE));	
			$connector = new ChargifyConnector($opt);
			$connector->cancelSubscription($_POST["chargifyCancelSubscription"]);
		}
	}
	function userActions($u)
	{
		if(!strlen($u->chargify_custid))
        	return 0;

		echo '<h3>Chargify Subscription</h3>';
		$d = get_option('chargify');
		$opt = array("api_key" => $d["chargifyApiKey"],"test_api_key" => $d["chargifyTestApiKey"],"domain" => $d["chargifyDomain"],"test_domain" => $d["chargifyTestDomain"],"test_mode"=>($d["chargifyMode"] == 'test'? TRUE : FALSE));	
		$connector = new ChargifyConnector($opt);
		$sub = $connector->getSubscriptionsByCustomerID($u->chargify_custid);
		if(is_array($sub))
		{
			foreach($sub as $s)
			{
				echo '<strong>'.$s->getProduct()->getName().'</strong><br>$'.$s->getProduct()->getPriceInDollars().' '.($s->getProduct()->getInterval() == 1 ? 'each '.$s->getProduct()->getIntervalUnit() : 'every '.$s->getProduct()->getInterval().' '.$s->getProduct()->getIntervalUnit().'s').'<br>'.$s->getProduct()->description . '<br>Subscription Status:<strong>'.$s->getState().'</strong><br><input type="checkbox" name="chargifyCancelSubscription" value="'.$s->id.'"><strong>Check this box to cancel this subscription</strong>';
			}
		}
	}
	function metaAccessBox()
	{
		global $post;
		
		$d = get_post_meta($post->ID, 'chargify_access', true); 
		
		$levels = $d["levels"];
		
		$products = chargify::products();
		$form = '<strong>User levels that can access this content</strong> Note: If you don\'t choose any levels below this will be a <strong>public<strong> post<br>';
		$form .= '<input type="hidden" name="access_noncename" id="access_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
		foreach($products as $p)
		{
			$form .= '<input type="checkbox" name="chargifyAccess['.$p->getHandle().']" value="'.$p->getHandle().'" '.($levels[$p->getHandle()] ? "checked" : "").'> '.$p->getName().'<br>';
		}
		echo $form;
	}	
	
	function subscriptionRedirect()
	{
		if ( wp_verify_nonce( $_POST['chargify_signup_noncename'], plugin_basename(__FILE__) ) && is_numeric($_POST["submit"]))
		{	
			$d = get_option("chargify");
			require_once( ABSPATH . WPINC . '/registration.php');	
			$user_login = sanitize_user( $_POST["chargifySignupEmail"] );
			$user_email = apply_filters( 'user_registration_email', $_POST["chargifySignupEmail"] );
			if(username_exists($user_login))
			{
				$_POST["chargify_signup_error"] = 'ERROR';
			}
			else
			{
				$user_pass = wp_generate_password();
				$d[$_POST["chargifySignupEmail"]]["user_login"] = $user_login;
				$d[$_POST["chargifySignupEmail"]]["user_email"] = $user_email;
				$d[$_POST["chargifySignupEmail"]]["user_pass"] = $user_pass;
				update_option("chargify",$d);

				$uri = '?first_name='.urlencode($_POST["chargifySignupFirst"]).'&last_name='.urlencode($_POST["chargifySignupLast"]).'&email='.urlencode($_POST["chargifySignupEmail"]).'&reference='.urlencode($_POST["chargifySignupEmail"]);
				if($d["chargifyMode"] == 'test')
				{
					header("Location: https://".$d["chargifyTestDomain"].".chargify.com/h/".$_POST["submit"]."/subscriptions/new".$uri);
					exit;
				}
				else
				{
					header("Location: https://".$d["chargifyDomain"].".chargify.com/h/".$_POST["submit"]."/subscriptions/new".$uri);
					exit;
				}
			}
		}
		if(function_exists('json_decode') && $_SERVER["CONTENT_TYPE"] === 'application/json')
		{
			global $wpdb;
			$sub_ids = json_decode(file_get_contents('php://input'));
file_put_contents("/tmp/postback",print_r($sub_ids,true),FILE_APPEND);
			if($sub_ids !== NULL && is_array($sub_ids))
			{
				$d = get_option('chargify');
				$opt = array("api_key" => $d["chargifyApiKey"],"test_api_key" => $d["chargifyTestApiKey"],"domain" => $d["chargifyDomain"],"test_domain" => $d["chargifyTestDomain"],"test_mode"=>($d["chargifyMode"] == 'test'? TRUE : FALSE));	
				$connector = new ChargifyConnector($opt);
				foreach($sub_ids as $id)
				{
					$sub = $connector->getSubscriptionsBySubscriptionId($id);
					if($sub->getStatus() == 'canceled')
					{
						$cur = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key = 'chargify_custid' AND meta_value = %s", $sub->getCustomer()->getId() ) );
						if ( $cur && $cur->user_id )
						{
							delete_usermeta( $cur->user_id, 'chargify_level'); 
						}
					}			
				}
			}
		}
	}

	function subscriptionPost($the_content)
	{
		$d = get_option("chargify");

		//Process full CC single form
		if ( wp_verify_nonce( $_POST['chargify_signupcc_noncename'], plugin_basename(__FILE__) ))
		{  
			$d = get_option('chargify');
			$opt = array("api_key" => $d["chargifyApiKey"],"test_api_key" => $d["chargifyTestApiKey"],"domain" => $d["chargifyDomain"],"test_domain" => $d["chargifyTestDomain"],"test_mode"=>($d["chargifyMode"] == 'test'? TRUE : FALSE));	
			$connector = new ChargifyConnector($opt);
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<subscription>
				<product_handle>' . $_POST["submit"] . '</product_handle>
				<customer_attributes>
				<first_name>'.$_POST["chargifySignupFirst"].'</first_name>
				<last_name>'.$_POST["chargifySignupLast"].'</last_name>
				<email>'.$_POST["chargifySignupEmail"].'</email>
				</customer_attributes>
				<credit_card_attributes>
				<first_name>'.$_POST["chargifySignupBillFirst"].'</first_name>
				<last_name>'.$_POST["chargifySignupBillLast"].'</last_name>
				<billing_address>'.$_POST["chargifySignupBillAddress"].'</billing_address>
				<billing_city>'.$_POST["chargifySignupBillCity"].'</billing_city>
				<billing_state>'.$_POST["chargifySignupBillState"].'</billing_state>
				<billing_zip>'.$_POST["chargifySignupBillZip"].'</billing_zip>
				<billing_country>'.$_POST["chargifySignupBillCountry"].'</billing_country>
				<full_number>'.$_POST["chargifySignupBillCc"].'</full_number>
				<expiration_month>'.$_POST["chargifySignupExpMo"].'</expiration_month>
				<expiration_year>'.$_POST["chargifySignupExpYr"].'</expiration_year>
				</credit_card_attributes>
			</subscription>';
			$res = $connector->createCustomerAndSubscription($xml);
			if(strlen($res->error))
			{
				return '<strong>'.$res->error.'</strong><br><br>'.$the_content;
			}
			else
			{
		        require_once( ABSPATH . WPINC . '/registration.php');	
				$user_login = sanitize_user( $_POST["chargifySignupEmail"] );
				$user_email = apply_filters( 'user_registration_email', $_POST["chargifySignupEmail"] );
				if(username_exists($user_login))
				{
					return "That email address is already in use, please choose another.".$the_content;
				}
				else
				{
					$user_pass = wp_generate_password();
					$user_id = wp_create_user( $user_login, $user_pass, $user_email );
					wp_new_user_notification($user_id, $user_pass);
					update_usermeta( $user_id, 'chargify_level', $res->getProduct()->getHandle()); 
					update_usermeta( $user_id, 'chargify_custid', $sub->getCustomer()->getId()); 
					return $d["chargifyThankYou"];
				}	
			}
		}

		//check to see if there was an error in the form processing step in chargifyRedirect
		if($_POST["chargify_signup_error"] == 'ERROR')
		{
			return "That email address is already in use, please choose another.".$the_content;
		}

		if($_GET["customer_reference"] && $_GET["subscription_id"] && !isset($_REQUEST["chargify.subscriptionPost"]))
		{
			$d = get_option('chargify');
			$opt = array("api_key" => $d["chargifyApiKey"],"test_api_key" => $d["chargifyTestApiKey"],"domain" => $d["chargifyDomain"],"test_domain" => $d["chargifyTestDomain"],"test_mode"=>($d["chargifyMode"] == 'test'? TRUE : FALSE));	
			$connector = new ChargifyConnector($opt);
			$sub = $connector->getSubscriptionsBySubscriptionId($_GET["subscription_id"]);
			if($sub->getState() == 'active' || $sub->getState() == 'trialing')
			{
				$email = $_GET["customer_reference"]; 
				if(isset($d[$email]))
                {
                    require_once( ABSPATH . WPINC . '/registration.php');
                    $user_id = wp_create_user( $d[$email]["user_login"], $d[$email]["user_pass"], $d[$email]["user_email"] );
                    if(is_wp_error($user_id))
                    {
                        return $user_id->get_error_message();
                    }
                    else
                    {
						//It's possible to hit this section twice depending on configuration
						//this ensures that it won't do all this work twice
						//it's a filthy hack but it works for now
						$_REQUEST["chargify.subscriptionPost"] = $user_id;
                        wp_new_user_notification($user_id, $d[$email]["user_pass"]);
                        
                        update_usermeta( $user_id, 'chargify_level', $sub->getProduct()->getHandle()); 
                        update_usermeta( $user_id, 'chargify_custid', $sub->getCustomer()->getId());
                        return $d["chargifyThankYou"];
                    }
                }
			}
		}	
		return $the_content;
	}
	function metaAccessBoxSave($post_id)
	{
		if ( !wp_verify_nonce( $_POST['access_noncename'], plugin_basename(__FILE__) ))
		{  
			return $post_id;  
		}
		if ( 'page' == $_POST['post_type'] ) 
		{
			if ( !current_user_can( 'edit_page', $post_id ))
			{
				return $post_id;
			}
		} 
		else 
		{
			if ( !current_user_can( 'edit_post', $post_id ))
			{
				return $post_id;
			}
		}

		$data["levels"] = $_POST['chargifyAccess'];
		
		update_post_meta($post_id, 'chargify_access', $data);
	}
	
		

	function activate()
	{
		$data = array(
			'chargifyApiKey'=>'APIKEY',
			'chargifyTestApiKey'=>'APIKEY',
			'chargifyDomain'=>'domain',
			'chargifyTestDomain'=>'domain',
			'chargifyMode'=>'test',
			'chargifySignupType'=>'default',
			'chargifyNoAccessAction'=>'default',
			'chargifyDefaultNoAccess'=>'You are not allowed to see this post. Please upgrade your account to see this content',
			'chargifyThankYou'=>'<strong>Subscription successfuly created!</strong>',
			'chargifySignupLink'=>'<<enter link to signup page here>>'
		);
		
		if(!get_option("chargify"))
        {
            add_option("chargify",$data);
        }
	}

    function deactivate()
    {
        delete_option("chargify");
    }

	function control()
	{
		add_options_page('Chargify Options','Chargify','activate_plugins','chargify-admin-settings',array('chargify','controlForm'));
	}

	function controlForm()
	{
		$d = get_option("chargify");

		if($_REQUEST["chargifyForm"] == 'Y')
		{
			$d["chargifyApiKey"] = $_REQUEST["chargifyApiKey"];
			$d["chargifyTestApiKey"] = $_REQUEST["chargifyTestApiKey"];
			$d["chargifyDomain"] = $_REQUEST["chargifyDomain"];
			$d["chargifyTestDomain"] = $_REQUEST["chargifyTestDomain"];
			$d["chargifyMode"] = $_REQUEST["chargifyMode"];
			$d["chargifyNoAccessAction"] = $_REQUEST["chargifyNoAccessAction"];
			$d["chargifyDefaultNoAccess"] = $_REQUEST["chargifyDefaultNoAccess"];
			$d["chargifyThankYou"] = $_REQUEST["chargifyThankYou"];
			$d["chargifySignupLink"] = $_REQUEST["chargifySignupLink"];
			$d["chargifySignupType"] = $_REQUEST["chargifySignupType"];

			update_option('chargify',$d);

			echo '<div class="updated"><p><strong>Options saved</strong></p></div>';
		}

        echo '<div class="wrap">
            <form name="chargify" method="post" action="">
            <input type="hidden" name="chargifyForm" value="Y">
            <h2>Chargify Settings</h2>
            <h3>Account Settings</h3>
            <p><em>Get this information from your Chargify account. Unless you have multiple accounts, the test values for the API Key and Test Domain are likely the same as their non-test counterparts.</em></p>
            <table class="form-table"> 
                <tr valign="top"> 
                    <th scope="row"><label>API Key:</label></th>
                    <td><input type="text" size="40" name="chargifyApiKey" value="'.$d['chargifyApiKey'].'"></td>
                </tr>
                <tr valign="top"> 
                    <th scope="row"><label>Test API Key:</label></th>
                    <td><input type="text" size="40" name="chargifyTestApiKey" value="'.$d['chargifyTestApiKey'].'"></td>
                </tr>
                <tr valign="top"> 
                    <th scope="row"><label>Domain:</label></th>
                    <td><input type="text" size="40" name="chargifyDomain" value="'.$d['chargifyDomain'].'">.chargify.com</td>
                </tr>
                <tr valign="top"> 
                    <th scope="row"><label>Test Domain:</label></th>
                    <td><input type="text" size="40" name="chargifyTestDomain" value="'.$d['chargifyTestDomain'].'">.chargify.com</td>
                </tr>
                <tr valign="top"> 
                    <th scope="row"><label>Mode:</label></th>
                    <td><input type="radio" name="chargifyMode" value="test" '.($d['chargifyMode'] == 'test' ? 'checked' : '').'>Test <input type="radio" name="chargifyMode" value="live" '.($d['chargifyMode'] == 'live' ? 'checked' : '').'>Live</td>
                </tr>   

            </table>
            <hr />
            <h3>Signup Type</h3>
            <p><em>How will your site process signups. <strong>NOTE: Most users should leave this set to default</strong>.</em></p>
            <table class="form-table"> 
                <tr valign="top"> 
                    <th scope="row"><input type="radio" name="chargifySignupType" value="default" '.($d['chargifySignupType'] == 'default' ? 'checked' : '').'>Default</th>
                    <td>When a user creates a subscription they will go to Chargify to enter their payment information and be redirected back to this site to see the thank you page</td>
                </tr>
                <tr valign="top"> 
                    <th scope="row"><input type="radio" name="chargifySignupType" value="api" '.($d['chargifySignupType'] == 'api' ? 'checked' : '').'>API Style</th>
                    <td><strong>Advanced Option: </strong>When a user creates a subscription they will enter their payment information on this site and the account will be created without the user ever leaving the site.<strong><br />IMPORTANT: Since you will be collecting credit card information in this mode you shouldn\'t activate this without having an SSL certificate on your site! You should be comfortable creating forms and working with program APIs.</td>
                </tr>
            </table>
            <hr />   

            <h3>No Access Message</h3>
            <p><em>Message to display if user doesn\'t have correct access to the content</em></p>
            <table class="form-table"> 
                <tr valign="top">           
                    <th scope="row"><input type="radio" name="chargifyNoAccessAction" value="default" '.($d["chargifyNoAccessAction"] == "default" ? "checked" : "").'> Show custom message</th>
                    <td><textarea cols=40 rows=3 style="display:inline-block; vertical-align:middle "name="chargifyDefaultNoAccess">'.$d['chargifyDefaultNoAccess'].'</textarea></td>           
                </tr>
                <tr valign="top">           
                    <th scope="row"><input type="radio" name="chargifyNoAccessAction" value="excerpt" '.($d["chargifyNoAccessAction"] == "excerpt" ? "checked" : "").'> Show post excerpt</th>
                    <td>&nbsp;</td>         
                </tr>
                </table>
                <hr />
                <h3>Signup Link</h3>
                <p><em>Enter the URL to the page on your site where you begin the signup process. You will have to create this as a page or post in Wordpress.</em></p>
                <table class="form-table">      
                <tr valign="top">           
                    <th scope="row"><label>Link to signup page.</label></th>
                    <td><input type="text" size="60" name="chargifySignupLink" value="'.$d['chargifySignupLink'].'"><span class="description">This will get shown on the page when a user doesn\'t haveaccess to the content</span></td>            
                </tr>
            </table>            
            <hr />
            <h3>Thank You Message</h3>
            <table class="form-table">              
                <tr valign="top">           
                    <th scope="row"><label>Thank you page text after a successful signup</label></th>
                    <td><textarea cols=40 rows=3 name="chargifyThankYou">'.$d['chargifyThankYou'].'</textarea></td>         
                </tr>
            </table>
                    
            <hr />
            <p class="submit"><input type="submit" name="Submit" value="Update Options" /></p>
            </form>
        </div>';
	}
	function createMetaAccessBox() 
	{
		add_meta_box( 'new-meta-boxes', 'Chargify Access Settings', array('chargify','metaAccessBox'), 'post', 'normal', 'high' );
		add_meta_box( 'new-meta-boxes', 'Chargify Access Settings', array('chargify','metaAccessBox'), 'page', 'normal', 'high' );
	}
}
?>
