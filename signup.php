<?php

//Whitelabel Basic Signup Page.
ini_set("display_errors","off");

//SETUP VARIABLES
$setup["username"] = "Example_Username";
$setup["API_KEY"] = "API_KEY_PROVIDED_BY_MAXO";
$setup["strict"] = 1; //Allow incomplete information and minor errors
$setup["success_URL"] = "http://voipportal.com.au/success";
$setup["API_BASE_URL"] = "https://api.maxo.com.au/wla/?user=".$setup["username"]."&key=".$setup["API_KEY"];
$setup['plan_list_cache_file'] = 'cache_plans.txt'; //File used to keep a local cache of the plan list

/*
NEVER ACTIVATE WEB-ORIGINATED SIGNUPS DIRECTLY THROUGH THE API.

This script is designed to insert signups to the PENDING SIGNUPS area of MADMIN. You can then
verify the account-holder information and activate the account if you wish.

YOU SHOULD SCRUTINIZE ALL WEB-ORIGINATED SIGNUPS. Fraud in the VoIP industry is ubiquitous and
every signup should be treated as suspicious until you can verify the customer's identity or
payment method.
*/

ignore_user_abort(); //If user presses stop button, the script continues anyway

if (file_exists($setup['plan_list_cache_file'])) {

	if (!$plans = json_decode(file_get_contents($setup['plan_list_cache_file']))) {
		echo "Error Decoding Plans from Cache. Attempting to write new cache.";
		$plan_raw = file_get_contents($setup["API_BASE_URL"]."&action=newCustomer&list_plans=1") or die("Error fetching plans from API");;
		if (!$plans = json_decode($plan_raw)) {
			echo "Error Decoding Plans from API. Contact the site administrator.";
		} else {
			file_put_contents ($setup['plan_list_cache_file'], $plan_raw); //new cache written
		}

	} else { //File exists, fetch new copy
	    if (filemtime($setup['plan_list_cache_file']) + 3600 < time()) { //cache expired
			$plan_raw = file_get_contents($setup["API_BASE_URL"]."&action=newCustomer&list_plans=1") or die("Error fetching plans from API");;
			if (!$plans = json_decode($plan_raw)) {
				echo "Error Decoding Plans from API. Contact the site administrator.";
			} else {
				file_put_contents ($setup['plan_list_cache_file'], $plan_raw); //new cache written
			}
		}
	
	}

} else {

	$plan_raw = file_get_contents($setup["API_BASE_URL"]."&action=newCustomer&list_plans=1") or die("Error fetching plans from API");;
	if (!$plans = json_decode($plan_raw)) {
		echo "Error Decoding Plans from API. Contact the site administrator.";
	} else {
		file_put_contents ($setup['plan_list_cache_file'], $plan_raw); //new cache written
	}
}

//Process submitted data
if ($_REQUEST["form_submitted"]) {

	$planid = $_REQUEST["selectbusplan"];
	$account_autotopup_amount = (substr($_REQUEST["account_autotopup_amount"], 0, 10)>0 && substr($_REQUEST["account_autotopup_amount"], 0, 10)<500?substr($_REQUEST["account_autotopup_amount"], 0, 10):20); //default Auto Topup
	$account_autotopup_at = (substr($_REQUEST["account_autotopup_at"], 0, 10)>0 && substr($_REQUEST["account_autotopup_at"], 0, 10)<500?substr($_REQUEST["account_autotopup_at"], 0, 10):10); //default Auto Topup
	
	$sb = array(
	"strict"                                => $setup["strict"],
	"sendemail"                             => $setup["sendemail"],
	"sendsms"                               => $setup["sendsms"],
	"account_cust_id"                       => substr($_REQUEST["account_cust_id"],0,20),
	"account_title"                         => substr($_REQUEST["account_title"],0,10),
	"account_username"                      => substr($_REQUEST["account_username"], 0, 50),
	"account_password"                      => substr($_REQUEST["account_password"], 0, 128),
	"account_first_name"                    => substr($_REQUEST["account_first_name"], 0, 50),
	"account_middle_name"                   => substr($_REQUEST["account_middle_name"], 0, 50),
	"account_last_name"                     => substr($_REQUEST["account_last_name"], 0, 50),
	"account_email"                         => substr($_REQUEST["account_email"], 0, 50),
	"account_mobile"                        => substr($_REQUEST["account_mobile"], 0, 20),
	"account_phone"                         => substr($_REQUEST["account_phone"], 0, 20),
	"account_abn"                           => substr($_REQUEST["account_abn"], 0, 50),
	"account_business_name"                 => substr($_REQUEST["account_business_name"], 0, 50),
	"account_address"                       => substr($_REQUEST["account_address"], 0, 50),
	"account_city"                          => substr($_REQUEST["account_city"], 0, 50),
	"account_post_code"                     => substr($_REQUEST["account_post_code"], 0, 50),
	"account_state"                         => substr($_REQUEST["account_state"], 0, 50),
	"account_country"                       => substr($_REQUEST["account_country"], 0, 50),
	"account_timezone"                      => substr($_REQUEST["account_timezone"], 0, 50),
	"account_autotopup_at"                  => $account_autotopup_at,
	"account_autotopup_amount"              => $account_autotopup_amount,
	"account_credit_limit"                  => "0",
	"account_postpaid"		                => "0",

	"account_acc_full_name"       		    => substr($_REQUEST["account_acc_full_name"], 0, 50),
	"account_acc_email"             	    => substr($_REQUEST["account_acc_email"], 0, 50),
	"account_acc_phone"           	        => substr($_REQUEST["account_acc_phone"], 0, 20),
	"account_acc_fax"              	        => substr($_REQUEST["account_acc_fax"], 0, 20),
	"account_acc_mobile"         	        => substr($_REQUEST["account_acc_mobile"], 0, 20),

	"account_tech_full_name"     	        => substr($_REQUEST["account_tech_full_name"], 0, 50),
	"account_tech_email"                    => substr($_REQUEST["account_tech_email"], 0, 50),
	"account_tech_phone"                    => substr($_REQUEST["account_tech_phone"], 0, 20),
	"account_tech_mobile"                   => substr($_REQUEST["account_tech_mobile"], 0, 20),

	"account_plan_id"                       => substr($planid, 0, 10),
	"account_plan_prorated"                 => $setup["account_plan_prorated"],

	"creditcard_number"                     => substr($_REQUEST["creditcard_number"], 0, 20),
	"creditcard_month"      	        	=> substr($_REQUEST["creditcard_month"], 0, 2),
	"creditcard_year"        	        	=> substr($_REQUEST["creditcard_year"], 0, 4),
	"creditcard_ccv"                        => substr($_REQUEST["creditcard_ccv"], 0, 4),
	"creditcard_name"                       => substr($_REQUEST["creditcard_name"], 0, 50),
	"creditcard_type"                       => substr($_REQUEST["creditcard_type"], 0, 20),

	"ipnd_service_building_type"                            => substr($_REQUEST["ipnd_service_building_type"], 0, 50),
	"ipnd_service_subunit_first_number"                     => substr($_REQUEST["ipnd_service_subunit_first_number"], 0, 50),
	"ipnd_service_subunit_first_number_suffix"              => substr($_REQUEST["ipnd_service_subunit_first_number_suffix"], 0, 50),
	"ipnd_service_building_floor_type"                      => substr($_REQUEST["ipnd_service_building_floor_type"], 0, 50),
	"ipnd_service_building_floor_number"                    => substr($_REQUEST["ipnd_service_building_floor_number"], 0, 50),
	"ipnd_service_building_floor_number_suffix"             => substr($_REQUEST["ipnd_service_building_floor_number_suffix"], 0, 50),
	"ipnd_service_street_house_number_1"                    => substr($_REQUEST["ipnd_service_street_house_number_1"], 0, 50),
	"ipnd_service_street_house_number_2"                    => substr($_REQUEST["ipnd_service_street_house_number_2"], 0, 50),
	"ipnd_service_street_house_first_number_suffix"         => substr($_REQUEST["ipnd_service_street_house_first_number_suffix"], 0, 50),
	"ipnd_service_street_name_1"                            => substr($_REQUEST["ipnd_service_street_name_1"], 0, 50),
	"ipnd_service_street_type_1"                            => substr($_REQUEST["ipnd_service_street_type_1"], 0, 50),
	"ipnd_service_street_suffix_1"                          => substr($_REQUEST["ipnd_service_street_suffix_1"], 0, 50),
	"ipnd_service_address_locality"                         => substr($_REQUEST["ipnd_service_address_locality"], 0, 50),
	"ipnd_service_address_post_code"                        => substr($_REQUEST["ipnd_service_address_post_code"], 0, 50),
	"ipnd_service_province_id"                              => substr($_REQUEST["ipnd_service_province_id"], 0, 50),
	"ipnd_service_country_id"                               => substr($_REQUEST["ipnd_service_country_id"], 0, 50));

	
	//If a field is null then make it an empty string - prevents JSON from setting the value to 0
	foreach($sb as $sbk => $sbv) if($sbv===false) $sb[$sbk]=(string)'';
	
	//Turn array of values into HTTP GET string
	$sbq = http_build_query($sb);
	
	//Submit to the API
	$newcustomer_raw = file_get_contents($setup["API_BASE_URL"]."&action=newCustomer&".$sbq) or die("Error posting to newCustomer API");;
	
	//Decode the JSON response
	$json = json_decode($newcustomer_raw,true);
	
	//Check we got a valid JSON response
	if(!is_array($json)){
		die('Invalid response received from the API');
	};

	if ($json["response"] == "OK") {
		echo "<script>window.location = '".$setup["success_URL"]."';";
	} else {
		if ($json["errors"]) {
			echo '<b>Errors:</b><br>';
			foreach ($json["errors"] as $error) {
				echo $error["error_msg"]."<br>";
			}
			echo '<br>';
		}

		if ($json["warnings"]) {
			echo '<b>Warnings:</b><br>';
			foreach ($json["warnings"] as $error) {
				echo $error["error_msg"]."<br>";
			}
			echo '<br>';
		}			
		echo '<a href="#signuptop" onclick="$(\'#overlay\').hide();">Back</a>';
	}
	exit;
}

//Street Type
require_once('inc/streetTypes.array.php');

$default_streets = "";
foreach ($street_type as $key => $value) {
	if ($key == "ST") {
		$dstreetsel = " selected";
	} else {
		$dstreetsel = "";
	}
$default_streets = $default_streets.'<option value="'.$key.'"'.$dstreetsel.'>'.$value.'</option>';

}


foreach ($plans->plans as $plan) {

	if ($plan->active == "1") {
		$busplans .= '<option value="'.$plan->account_plan_id.'">'.$plan->name.' @ $'.$plan->price.'/month </option>';
	} 
}
?> 


<script src="jquery-2.2.4.min.js"></script>
		
<script type="text/javascript">
$(document).ready(function() {
		$('#signlookup').submit(function() { // catch the form's submit event
		$('#signresult').html('<img src="loading.gif">');
		$('#overlay').show();

		$.ajax({ // create an AJAX call...
			data: $(this).serialize(), // get the form data
			type: $(this).attr('method'), // GET or POST
			url: $(this).attr('action'), // the file to call
			success: function(response) { // on success..
				$('#signresult').html(response); // update the DIV
			}
		});

		return false; // cancel original event to prevent form submitting
	});




	//attach handler
	$("#overlay").click(function(ev){
		//did they click the correct div
		if ($(ev.target).attr("id") == "overlay"){
			$('#overlay').hide();
		}
	});

});
</script>

<script type="text/javascript">

function showhide() {


	if ($('#selectaccounts').val() == "YES")  {
		 $('#accounts').show();	
	} else {
		 $('#accounts').hide();
	}

	if ($('#selecttech').val() == "YES")  {
		$('#tech').show();
	} else {
		$('#tech').hide();
	}


	return 0;
};

</script>

<a name="signuptop"></a>
<form id="signlookup" method="post" action="">
<table width="100%" border="0" cellpadding="2">
 
  <tr class="tableHeading"> 
    <td colspan="2"><b>Account Holder Details</b></td>
  </tr>
  <tr class="r1c1"> 
    <td>Title:</td>
    <td><select name="account_title" id="title">
        <option value="Dr">Dr</option>
        <option value="Miss">Miss</option>
        <option value="Mr" selected>Mr</option>
        <option value="Mrs">Mrs</option>
        <option value="Ms">Ms</option>
      </select></td>
  </tr>
  <tr class="r1c2"> 
    <td>First Name:</td>
    <td> <input name="account_first_name" type="text" id="account_first_name"> 
      <span id="business3"><strong>Business Owner Details</strong></span></td>
  </tr>
  <tr class="r1c1"> 
    <td>Middle Name:</td>
    <td><input name="account_middle_name" type="text" id="account_middle_name"> 
    </td>
  </tr>
  <tr class="r1c2"> 
    <td>Last Name:</td>
    <td><input name="account_last_name" type="text" id="account_last_name"></td>
  </tr>
  <tr class="r1c1"> 
    <td>Email:</td>
    <td> <input name="account_email" type="text" id="account_email"></td>
  </tr>
  <tr class="r1c2"> 
    <td>Mobile No.:</td>
    <td> <input name="account_mobile" type="text" id="account_mobile">
      <em></em></td>
  </tr>
  <tbody id="business2">
    <tr class="r1c1"> 
      <td colspan="2">Accounts Payable Contact: 
        <select id="selectaccounts" name="selectaccounts" onclick="javascript:showhide();">
          <option>NO</option>
          <option>YES</option>
        </select>
		</td></tr>
	<tr class="r1c2">
		<td colspan="2">
        Technical Contact: 
        <select id="selecttech" name="selecttech" onclick="javascript:showhide();">
          <option>NO</option>
          <option>YES</option>
        </select> </td>
    </tr>
  </tbody>
  <tbody id="accounts" style="display: none;">
    <tr class="tableHeading"> 
      <td colspan="2"><b>Accounts Contact</b></td>
    </tr>
    <tr class="r1c1"> 
      <td>Full Name:</td>
      <td> <input name="account_acc_full_name" type="text" id="account_acc_full_name"> 
      </td>
    </tr>
    <tr class="r1c2"> 
      <td>Email:</td>
      <td> <input name="account_acc_email" type="text" id="account_acc_email"> 
      </td>
    </tr>
    <tr class="r1c1"> 
      <td>Phone No:</td>
      <td><input name="account_acc_phone" type="text" id="account_acc_phone"> 
      </td>
    </tr>
    <tr class="r1c2"> 
      <td>Fax No:</td>
      <td><input name="account_acc_fax" type="text" id="account_acc_fax"></td>
    </tr>
    <tr class="r1c1"> 
      <td>Mobile No.:</td>
      <td> <input name="account_acc_mobile" type="text" id="account_acc_mobile"> 
      </td>
    </tr>
  </tbody>
  <tbody id="tech" style="display: none;">
    <tr class="tableHeading"> 
      <td colspan="2"><b>Tech Contact</b></td>
    </tr>
    <tr class="r1c1"> 
      <td>Full Name:</td>
      <td> <input name="account_tech_full_name" type="text" id="account_tech_full_name"> 
      </td>
    </tr>
    <tr class="r1c2"> 
      <td>Email:</td>
      <td> <input name="account_tech_email" type="text" id="account_tech_email"> 
      </td>
    </tr>
    <tr class="r1c1"> 
      <td>Phone No:</td>
      <td><input name="account_tech_phone" type="text" id="account_tech_phone"> 
      </td>
    </tr>
    <tr class="r1c2"> 
      <td>Mobile No.:</td>
      <td> <input name="account_tech_mobile" type="text" id="account_tech_mobile"> 
      </td>
    </tr>
  </tbody>
  <tbody id="business">
    <tr class="tableHeading"> 
      <td colspan="2"><b>Business Details:</b></td>
    </tr>
    <tr class="r1c1"> 
      <td>Business Name:</td>
      <td> <input name="account_business_name" type="text" id="account_business_name"> 
      </td>
    </tr>
    <tr class="r1c2"> 
      <td>ABN:</td>
      <td><input name="account_abn" type="text" id="account_abn"> 
      </td>
    </tr>
  </tbody>
  <tr class="r1c1"> 
    <td>Phone No:</td>
    <td><input name="account_phone" type="text" id="account_phone"></td>
  </tr>
  <tr class="r1c2"> 
    <td>Postal Address:</td>
    <td> <input name="account_address" type="text" id="account_address">
      (PO Box or your street address)</td>
  </tr>
  <tr class="r1c1"> 
    <td>City:</td>
    <td> <input name="account_city" type="text" id="account_city"></td>
  </tr>
  <tr class="r1c2"> 
    <td>Post Code:</td>
    <td> <input name="account_post_code" type="text" id="account_post_code"></td>
  </tr>
  <tr class="r1c1"> 
    <td>State:</td>
    <td> <select name="account_state" id="account_state">
        <option value="NSW">NSW</option>
        <option value="VIC">VIC</option>
        <option value="QLD">QLD</option>
        <option value="SA">SA</option>
        <option value="WA">WA</option>
        <option value="ACT">ACT</option>
        <option value="TAS">TAS</option>
        <option value="NT">NT</option>
      </select> </td>
  </tr>
  <tr class="r1c2"> 
    <td>Country</td>
    <td> <input name="account_country" type="text" id="account_country" value="Australia"></td>
  </tr>
  <tr class="tableHeading"> 
    <td colspan="2"><b>Service Address Details</b></td>
  </tr>
  <tr class="r1c2"> 
    <td colspan="2">IPND Address: Enter the address where the VoIP service will be located.<br>
      These are the details emergency services will receive when you call 000.</td>
  </tr>
  <tr class="r1c1"> 
    <td>Building Type:</td>
    <td> <select name="ipnd_service_building_type" id="ipnd_service_building_type">
        <option value="APT">Apartment</option>
        <option value="F">Flat</option>
        <option value="FY">Factory</option>
        <option value="MB">Marine berth</option>
        <option value="OFF">Office</option>
        <option value="RM">Room</option>
        <option value="SE">Suite</option>
        <option value="SHED">Shed</option>
        <option value="SHOP">Shop</option>
        <option value="SITE" selected>Site / House</option>
        <option value="SL">Stall</option>
        <option value="U">Unit</option>
        <option value="VLLA">Villa</option>
        <option value="WE">Warehouse</option>
      </select> </td>
  </tr>
  <tr class="r1c2"> 
    <td>Sub-unit Number:</td>
    <td> <input name="ipnd_service_subunit_first_number" type="text" id="ipnd_service_subunit_first_number" size="5" maxlength="5">
      eg <strong>2 (For Unit <font color="#FF0000">2</font>A)</strong></td>
  </tr>
  <tr class="r1c1"> 
    <td>Sub-unit Suffix:</td>
    <td> <input name="ipnd_service_subunit_first_number_suffix" type="text" id="ipnd_service_subunit_first_number_suffix" size="5" maxlength="1">
      eg <strong>A (For Unit 2<font color="#FF0000">A</font>)</strong></td>
  </tr>
  <tr class="r1c2"> 
    <td>Floor Type:</td>
    <td> <select name="ipnd_service_building_floor_type">
        <option value="B">Basement</option>
        <option value="FL">Floor</option>
        <option value="G" selected>Ground Floor</option>
        <option value="L">Level</option>
        <option value="LG">Lower Ground Floor</option>
        <option value="M">Mezzanine</option>
        <option value="UG">Upper Ground Floor</option>
      </select> </td>
  </tr>
  <tr class="r1c1"> 
    <td>Floor Number:</td>
    <td> <input name="ipnd_service_building_floor_number" type="text" id="ipnd_service_building_floor_number" size="5" maxlength="5">
      eg <strong>15 (For Level<font color="#FF0000"> 15</font></strong><font color="#000000">&nbsp;</font><strong>)</strong></td>
  </tr>
  <tr class="r1c2"> 
    <td>Floor Suffix:</td>
    <td> <input name="ipnd_service_building_floor_number_suffix" type="text" id="ipnd_service_building_floor_number_suffix" size="5" maxlength="5">
      eg <strong>leave it blank (For Level<font color="#000000"> 15</font>)</strong></td>
  </tr>
  <tr class="r1c1"> 
    <td>Street Number:</td>
    <td> <input name="ipnd_service_street_house_number_1" type="text" id="ipnd_service_street_house_number_1" size="5" maxlength="5">
      to 
      <input name="ipnd_service_street_house_number_2" type="text" id="ipnd_service_street_house_number_2" size="5" maxlength="5"> 
    </td>
  </tr>
  <tr class="r1c2"> 
    <td>Street Number Suffix:</td>
    <td> <input name="ipnd_service_street_house_first_number_suffix" type="text" id="ipnd_service_street_house_first_number_suffix" size="5" maxlength="1">
      eg <strong>A (For 58<font color="#FF0000">A</font> Short St)</strong></td>
  </tr>
  <tr class="r1c1"> 
    <td>Street Name:</td>
    <td> <input name="ipnd_service_street_name_1" type="text" id="ipnd_service_street_name_1" size="30" maxlength="50"> 
    </td>
  </tr>
  <tr class="r1c2"> 
    <td>Street Type:</td>
    <td> <select name="ipnd_service_street_type_1" id="ipnd_service_street_type_1">
        <?=$default_streets;?> </select> </td>
  </tr>
  <tr class="r1c1"> 
    <td>Street Suffix:</td>
    <td> <select name="ipnd_service_street_suffix_1" id="ipnd_service_street_suffix_1">
        <option value="North">North</option>
        <option value="South">South</option>
        <option value="East">East</option>
        <option value="West">West</option>
        <option value="" selected>(None)</option>
      </select> </td>
  </tr>
  <tr class="r1c2"> 
    <td>City/Suburb/Town:</td>
    <td> <input name="ipnd_service_address_locality" type="text" id="ipnd_service_address_locality" size="30" maxlength="50"> 
    </td>
  </tr>
  <tr class="r1c1"> 
    <td>State:</td>
    <td> <select name="ipnd_service_province_id" id="ipnd_service_province_id">
        <option value="NSW">New South Wales</option>
        <option value="QLD">Queensland</option>
        <option value="VIC">Victoria</option>
        <option value="TAS">Tasmania</option>
        <option value="SA">South Australia</option>
        <option value="WA">Western Australia</option>
        <option value="NT">Northern Territory</option>
        <option value="ACT">Australia Capital Territory</option>
      </select> </td>
  </tr>
  <tr class="r1c2"> 
    <td>Postcode:</td>
    <td> <input name="ipnd_service_address_post_code" type="text" id="ipnd_service_address_post_code" size="10" maxlength="10"> 
      <input name="ipnd_service_country_id" type="hidden" id="ipnd_service_country_id" value="AU" size="30" maxlength="50"> 
    </td>
  </tr>
  <tr class="tableHeading"> 
    <td colspan="2"><b>My Account Details</b></td>
  </tr>
  <tr class="r1c1"> 
    <td width="140">Username: </td>
    <td> <input name="account_username" type="text" id="account_username" maxlength="20">
      (min: 6 characters including 1 number) </td>
  </tr>
  <tr class="r1c2"> 
    <td>Password: </td>
    <td> <input name="account_password" type="password" id="account_password" maxlength="20">
      (min: 6 characters including 1 number)</td>
  </tr>


  <tbody id="business4">
    <tr class="r1c1"> 
      <td>Plan:</td>
      <td><select name="selectbusplan" id="selectbusplan">
          <option value="0" selected>Choose a plan!</option>
          <?=$busplans;?> </select> </td>
    </tr>
  </tbody>
 
 
     <tr class="tableHeading"> 
    <td colspan="2"><b>Credit Card Information (Optional)</b></td>
  </tr>
   <tr class="r1c2">
      <td>Name on Card:</td>
      <td><input name="creditcard_name" type="text" id="creditcard_name" size="20"></td>
    </tr>
    <tr class="r1c1"> 
      <td>Card Number:</td>
      <td> <input name="creditcard_number" type="text" size="20" maxlength="16"> </td>
    </tr>
    <tr class="r1c2"> 
      <td>Card Type:</td>
      <td><select name="creditcard_type">
          <option>Visa</option>
          <option>MasterCard</option>
		  <option>American Express</option>
        </select></td>
    </tr>
    <tr class="r1c1"> 
      <td>Expiry Date:</td>
      <td><select name="creditcard_month">
          <option>01</option>
          <option>02</option>
          <option>03</option>
          <option>04</option>
          <option>05</option>
          <option>06</option>
          <option>07</option>
          <option>08</option>
          <option>09</option>
          <option>10</option>
          <option>11</option>
          <option>12</option>
        </select> 
		<?php
		$yearExpires = "<select id='creditcard_year' name='creditcard_year'>";
				$selectyear = $currentyear = date("Y");
				while ($currentyear <= (date("Y")+10)) {
					$yearExpires .= "<option value='{$currentyear}'".($currentyear==$selectyear?" selected='selected'":"").">{$currentyear}</option>";
					$currentyear++;
				}
				$yearExpires .= "</select>";
				echo $yearExpires;
		?>
	 </td>
    </tr>
    <tr class="r1c2"> 
      <td>Signature No:</td>
      <td><input name="creditcard_ccv" type="text" size="5" maxlength="4"> (On signature panel for Visa/Mastercard, front of card for American Express)</td>
    </tr>
 <tr class="r1c1">
	<td>Prepaid Auto Topup Amount:</td>
	<td><select id="account_autotopup_amount" name="account_autotopup_amount">	
	<option value="20">$20</option>
	<option value="30">$30</option>
	<option value="40">$40</option>
	<option value="50">$50</option>
	<option value="75">$75</option>
	<option value="100">$100</option>
	<option value="250">$250</option>
	<option value="500">$500</option>		
	</select> 
	
	</td>
  </tr>
</table>

<br><input type="hidden" name="form_submitted" value="1"><input type="submit" name="signup_submitted" value="Process Application"></form>
<div id="overlay" style="display: none;     position: fixed;     left: 0px;     top: 0px;     width:100%;     height:100%;     text-align:center;     z-index: 1000; background: rgb(207, 207, 207,0.6);"> <div id="signresult" style=" width:500px;     margin: 100px auto;     background-color: #fff;     border:1px solid #000;     padding:15px;     text-align:left;"></div></div>




