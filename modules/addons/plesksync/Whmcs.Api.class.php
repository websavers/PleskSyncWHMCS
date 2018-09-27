<?php
// ----------------------------------------------------------------------------------------------------------------------
// [ WhmcsApiWrapper ]
// ----------------------------------------------------------------------------------------------------------------------
// A simple PHP wrapper class for the WHMCS API
// ---
// Version: WhmcsApiWrapper v0.10 (Compatible with WHMCS v4.3) as of (Sept-23-2010)
// ---
// Written by: Shawn C. Reimerdes <shawnreimerdes@users.sourceforge.net>
// Website: http://sourceforge.net/users/shawnreimerdes
// ---
// Reference: http://wiki.whmcs.com/API:Functions
// ---
// DISCLAIMER: This Open Source software/code is provided AS IS, feel free to use it anywhere and anyway you wish.
//	       The code is included in the hopes you will find it useful and perhaps contribute to it.
// ----------------------------------------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------------------------------------

class WhmcsApiWrapper {
 
	protected $api_url;				// location of API via URL (http://yoursite.com/whmcs/api.php)
	
	var $api_username;		 	// admin username with API Access enabled
	var $api_password_hash;	 	 	// password for admin username 
       
	
	protected $bTransactionResultCode;        	// API communicaton result: 0 = failed, 1 = success
	protected $arrTransactionResultData;   		// API communicaton result: error message 
	
				
// ========================================================================================================================
// Public Class Functions 
// ========================================================================================================================    

// -------------------------------------------------------------------------------------------------------------- 
public function setApiUrl($url) {
 
        $this->api_url = $url;
} 
// -------------------------------------------------------------------------------------------------------------- 
public function setLoginAccess($username,$password) {
 
        $this->api_username = $username;
	
	if (strlen($password) > 25) $this->api_password_hash = $password;
	else $this->api_password_hash = md5($password);	
}
// -------------------------------------------------------------------------------------------------------------- 
public function setLoginAccessAutomatic($db_username,$db_password, $db_name, $source = "localhost") {

	  // will get the first admin in whmcs with access permission to API 
          $sqlconnection = mysql_connect($source,$db_username,$db_password);											  					
          mysql_select_db($db_name);
         
          $query = mysql_query("SELECT username, password FROM `tbladmins` LEFT JOIN `tbladminperms` ON `tbladmins`.roleid = `tbladminperms`.roleid  WHERE `tbladminperms`.permid = '81' ORDER BY id ASC LIMIT 1", $sqlconnection);    // whmcs admin permission #81 = "API Access"
         
          $row = mysql_fetch_array($query);
          mysql_close($sqlconnection);
          
          if (!isset($row) || empty($row)) 
                  echo '<span style="color:red">setLoginAccessAutomatic() - Auto-detect admin failed:</span> You need to enable "API Access" for at least one of your admins in Configuration -> <a href="../admin/configadminroles.php" target="_blank">Administrator Roles</a>';
         
	 $this->api_username = $row["username"];
	 $this->api_password_hash = $row["password"];
	 
}
// -------------------------------------------------------------------------------------------------------------- 
public function getTransactionData() {
 
	return $this->arrTransactionResultData;
}
// --------------------------------------------------------------------------------------------------------------

// ========================================================================================================================
// Private Class Functions
// ========================================================================================================================

private function makeTransaction($url_postfields)  {
       
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $this->api_url);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_TIMEOUT, 100);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $url_postfields);
       $data = curl_exec($ch);
       curl_close($ch);
                                      
       $data = explode(";",$data);

	
       if ($data[0] == 'result=success') {    		    

		 $this->bTransactionSuccessCode = TRUE;
		 		 
		 foreach ($data AS $objTemp) {   // store it a tad bit cleaner: create an associative array 
		  
			   $arrTemp = explode("=",$objTemp);
			   if (!empty($arrTemp[1])) $objResult[$arrTemp[0]] = utf8_encode($arrTemp[1]);
			 
		  }  	
     
       } else {

		 $this->bTransactionSuccessCode = FALSE;
		 $objResult["errormessage"] = str_ireplace('message=','',$data[1]);		    	  
        }       
	  
       $this->arrTransactionResultData = $objResult;       
       
return $this->bTransactionSuccessCode;
} 
// --------------------------------------------------------------------------------------------------------------


// ========================================================================================================================
// Client Management 
// ========================================================================================================================
// -------------------------------------------------------------------------------------------------------------- 
public function addClient($firstname, $lastname, $companyname = "", $email, $password, $address1, $address2 = "", $city, $state, $postcode, $country = "US", $phone, $no_email = "true", $default_currency = "1", $notes = "", $groupid = "", $cctype = "", $ccnumber = "", $ccexpiredate = "", $startdate = "", $issuenumber = "", $customfields = "" ) {

	// Client Management - http://wiki.whmcs.com/API:Add_Client
	
       $url_postfields["action"] = "addclient";              
       // ---                           
       $url_postfields["username"] = $this->api_username;
       $url_postfields["password"] = $this->api_password_hash;

       // ---
       $url_postfields["firstname"] = $firstname;
       $url_postfields["lastname"] = $lastname;
       if (!empty($companyname)) $url_postfields["companyname"] = $companyname; 	// (optional)
       $url_postfields["email"] = $email;
       $url_postfields["address1"] = $address1;
       if (!empty($address2)) $url_postfields["address2"] = $address2;  		// (optional)
       $url_postfields["city"] = $city;
       $url_postfields["state"] = $state;
       $url_postfields["postcode"] = $postcode;
       $url_postfields["country"] = $country;		// - two letter ISO country code
       $url_postfields["phonenumber"] = $phone;
       $url_postfields["password2"] = $password;		// - plain text password for the new user account
       $url_postfields["currency"] = $default_currency;
       $url_postfields["noemail"] = $no_email;		// - pass as true to surpress the client signup welcome email sending
       // ---       
       if (!empty($notes)) $url_postfields["notes"] = $notes;
       if (!empty($cctype)) $url_postfields["cctype"] = $cctype;  	// (optional) - Visa, Mastercard, etc...
       if (!empty($cardnum)) $url_postfields["cardnum"] = $cardnum;       // (optional) 
       if (!empty($expdate)) $url_postfields["expdate"] = $expdate;	// (optional) - in the format MMYY
       if (!empty($startdate)) $url_postfields["startdate"] = $startdate;	// (optional) 
       if (!empty($issuenumber)) $url_postfields["issuenumber"] = $issuenumber;       // (optional) 
       if (!empty($customfields)) $url_postfields["customfields"] = $customfields;       // (optional) - a base64 encoded serialized array of custom field values
       if (!empty($groupid)) $url_postfields["groupid"] = $groupid;	// (optional) 	- used to assign the client to a client group
     
return $this->makeTransaction($url_postfields);
}
// -------------------------------------------------------------------------------------------------------------- 
public function updateClient() {
	// Client Management - http://wiki.whmcs.com/API:Add_Client
}
// -------------------------------------------------------------------------------------------------------------- 
public function getClients() {
 	// Client Management - http://wiki.whmcs.com/API:Get_Clients

}
// -------------------------------------------------------------------------------------------------------------- 
public function deleteClient() {
 	// Client Management - http://wiki.whmcs.com/API:Delete_Client

}
// -------------------------------------------------------------------------------------------------------------- 
public function getClientsDetails() {
	// Client Management - http://wiki.whmcs.com/API:Get_Clients_Details

}
// -------------------------------------------------------------------------------------------------------------- 
public function getClientsProducts() {
	// Client Management - http://wiki.whmcs.com/API:Get_Clients_Products
}
// -------------------------------------------------------------------------------------------------------------- 
public function getClientsPassword() {
	// Client Management - http://wiki.whmcs.com/API:Get_Clients_Password
}
// -------------------------------------------------------------------------------------------------------------- 
public function sendEmail() {
	// Client Management - http://wiki.whmcs.com/API:Send_Email
}

// ========================================================================================================================
// Order Handling
// ========================================================================================================================
 
// --------------------------------------------------------------------------------------------------------------
public function getOrders() {
 	// Order Handling - http://wiki.whmcs.com/API:Get_Orders

}
// --------------------------------------------------------------------------------------------------------------
public function getOrderStatuses() {
 	// Order Handling - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function addOrder($domainname, $clientid, $productid, $paymentmethod, $billingcycle = "monthly", $noinvoice = "true", $noemail = "true", $addons = "", $customfields = "", $configoptions = "", $domaintype = "", $regperiod = "", $dnsmanagement = "", $emailforwarding = "", $idprotection = "", $eppcode = "", $nameserver1 = "", $nameserver2 = "", $nameserver3 = "", $nameserver4 = "", $promocode = "", $affid = "", $clientip = "") {

 	// Order Handling - http://wiki.whmcs.com/API:Add_Order
	
	$url_postfields["action"] = "addorder";
	// ---
        $url_postfields["username"] = $this->api_username;
        $url_postfields["password"] = $this->api_password_hash;
        // ---	
	$url_postfields["clientid"] = $clientid;		// client id for order
	$url_postfields["pid"] = $productid;              // product id
	$url_postfields["domain"] = $domainname;		// domain name
	
	$url_postfields["noinvoice"] = $noinvoice;	// set true to not generate an invoice for this order
	$url_postfields["noemail"] = $noemail;		// set true to surpress the order confirmation email
	
	$url_postfields["billingcycle"] = $billingcycle;		// onetime, monthly, quarterly, semiannually, etc..
	$url_postfields["paymentmethod"] = $paymentmethod;        // paypal, authorize, webmoney etc...
  
	if (!empty($addons)) $url_postfields["addons"] = $addons;	 				// addons - comma seperated list of addon ids
	if (!empty($dnsmanagement)) $url_postfields["customfields"] = $customfields;	 	// a base64 encoded serialized array of custom field values
	if (!empty($dnsmanagement)) $url_postfields["configoptions"] = $configoptions;		// a base64 encoded serialized array of configurable product options	
	if (!empty($dnsmanagement)) $url_postfields["domaintype"] = $domaintype;	 		// set only for domain registration - register or transfer
	if (!empty($dnsmanagement)) $url_postfields["regperiod"] = $regperiod;	 		// set only for domain registration - 1,2,3,etc..
	if (!empty($dnsmanagement)) $url_postfields["dnsmanagement"] = $dnsmanagement;	 	// set only for domain registration - true to enable
	if (!empty($emailforwarding)) $url_postfields["emailforwarding"] = $emailforwarding;	// set only for domain registration - true to enable	
	if (!empty($idprotection)) $url_postfields["idprotection"] = $idprotection;	 	// set only for domain registration - true to enable	
	if (!empty($eppcode)) $url_postfields["eppcode"] = $eppcode;	 			//  set only for domain transfer
	if (!empty($nameserver1)) $url_postfields["nameserver1"] = $nameserver1;		 	// set only for domain registration - DNS Nameserver #1
	if (!empty($nameserver2)) $url_postfields["nameserver2"] = $nameserver2;	 		// set only for domain registration - DNS Nameserver #2
	if (!empty($nameserver3)) $url_postfields["nameserver3"] = $nameserver3;	 		// set only for domain registration - DNS Nameserver #3
	if (!empty($nameserver4)) $url_postfields["nameserver4"] = $nameserver4;	 		// set only for domain registration - DNS Nameserver #4	
	if (!empty($promocode)) $url_postfields["promocode"] = $promocode;	 		// pass coupon code to apply to the order (optional)	
	if (!empty($affid)) $url_postfields["affid"] = $affid;	 				// affiliate ID if you want to assign the order to an affiliate (optional)
	if (!empty($clientip)) $url_postfields["clientip"] = $clientip;	 			// can be used to pass the customers IP (optional)
       

return $this->makeTransaction($url_postfields);
}
// --------------------------------------------------------------------------------------------------------------
public function acceptOrder($orderid) {
 	// Order Handling - http://wiki.whmcs.com/API:Accept_Order
	
	$url_postfields["action"] = "acceptorder";
	// ---
        $url_postfields["username"] = $this->api_username;
        $url_postfields["password"] = $this->api_password_hash;
        // ---	
	$url_postfields["orderid"] = $orderid;
	
return $this->makeTransaction($url_postfields);
}
// --------------------------------------------------------------------------------------------------------------
public function pendingOrder() {
 	// Order Handling - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function cancelOrder() {
 	// Order Handling - http://wiki.whmcs.com/API:Cancel_Order

}
// --------------------------------------------------------------------------------------------------------------
public function fraudOrder() {
 	// Order Handling - http://wiki.whmcs.com/API:Fraud_Order

}
// --------------------------------------------------------------------------------------------------------------
public function deleteOrder() {
 	// Order Handling - http://wiki.whmcs.com/API:Delete_Order

}
// --------------------------------------------------------------------------------------------------------------

// ========================================================================================================================
// Module Commands
// ========================================================================================================================
// --------------------------------------------------------------------------------------------------------------
public function moduleCreate() {
 	// Module Commands - http://wiki.whmcs.com/API:Module_Create

}
// --------------------------------------------------------------------------------------------------------------
public function moduleSuspend() {
 	// Module Commands - http://wiki.whmcs.com/API:Module_Suspend

}
// --------------------------------------------------------------------------------------------------------------
public function moduleUnsuspend() {
 	// Module Commands - http://wiki.whmcs.com/API:Module_Unsuspend

}
// --------------------------------------------------------------------------------------------------------------
public function moduleTerminate() {
 	// Module Commands - http://wiki.whmcs.com/API:Module_Terminate

}
// --------------------------------------------------------------------------------------------------------------

// ========================================================================================================================
// Payments/Billing
// ========================================================================================================================
 
// --------------------------------------------------------------------------------------------------------------
public function getInvoice() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function getPaymentMethods() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// -------------------------------------------------------------------------------------------------------------- 
public function createInvoice() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function addBillableItem() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function addInvoicePayment() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function addTransaction() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function addCredit() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------
public function CapturePayment() {
 	// Payments/Billing - http://wiki.whmcs.com/API:

}
// --------------------------------------------------------------------------------------------------------------


// ========================================================================================================================
// Miscellaneous
// ========================================================================================================================
 
// --------------------------------------------------------------------------------------------------------------
public function domainWhoisLookup() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Domain_WHOIS_Lookup

} 
// --------------------------------------------------------------------------------------------------------------
public function getActivityLog() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_Activity_Log

}
// --------------------------------------------------------------------------------------------------------------
public function getAdminDetails() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_Admin_Details

}
// --------------------------------------------------------------------------------------------------------------
public function updateAdminNotes() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Update_Admin_Notes

}
// --------------------------------------------------------------------------------------------------------------
public function getCurrencies() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_Currencies

}
// --------------------------------------------------------------------------------------------------------------
public function getEmailTemplates() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_Email_Templates

}
// --------------------------------------------------------------------------------------------------------------
public function getTodoItems() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_To-Do_Items

}
// --------------------------------------------------------------------------------------------------------------
public function getTodoItemStatuses() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_To-Do_Item_Statuses

}
// --------------------------------------------------------------------------------------------------------------
public function getStaffOnline() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_Staff_Online

}
// --------------------------------------------------------------------------------------------------------------
public function getStats() {
 	// Miscellaneous - http://wiki.whmcs.com/API:Get_Stats

}
// --------------------------------------------------------------------------------------------------------------
public function encryptPassword($password) {
 	
	// Miscellaneous - http://wiki.whmcs.com/API:Encrypt_Password
	
	$url_postfields["action"] = "encryptpassword";
        // ---
        $url_postfields["username"] = $this->api_username;
        $url_postfields["password"] = $this->api_password_hash;
        // ---		
	$url_postfields["password2"] = $password;

return $this->makeTransaction($url_postfields);
}
// --------------------------------------------------------------------------------------------------------------
public function decryptPassword($password) {
 	
	// Miscellaneous - http://wiki.whmcs.com/API:Decrypt_Password

	$url_postfields["action"] = "decryptpassword";
        // ---
        $url_postfields["username"] = $this->api_username;
        $url_postfields["password"] = $this->api_password_hash;
        // ---	
	$url_postfields["password2"] = $password;

return $this->makeTransaction($url_postfields);
}
    
// ========================================================================================================================
// Support Tickets
// ========================================================================================================================
 
// --------------------------------------------------------------------------------------------------------------
public function getSuppportDepartments() {
 	// Support Tickets - http://wiki.whmcs.com/API:Get_Support_Departments

}    
// --------------------------------------------------------------------------------------------------------------
public function getSuppportStatuses() {
 	// Support Tickets - http://wiki.whmcs.com/API:Get_Support_Statuses

}
// --------------------------------------------------------------------------------------------------------------
public function getTickets() {
 	// Support Tickets - http://wiki.whmcs.com/API:Get_Tickets

}
// --------------------------------------------------------------------------------------------------------------
public function getTicket() {
 	// Support Tickets - http://wiki.whmcs.com/API:Get_Ticket

}
// --------------------------------------------------------------------------------------------------------------
public function getTicketPredefinedCats() {
 	// Support Tickets - http://wiki.whmcs.com/API:Get_Ticket_Predefined_Cats

}
// --------------------------------------------------------------------------------------------------------------
public function getTicketPredefinedReplies() {
 	// Support Tickets - http://wiki.whmcs.com/API:Get_Ticket_Predefined_Replies

}
// --------------------------------------------------------------------------------------------------------------
public function openTicket() {
 	// Support Tickets - http://wiki.whmcs.com/API:Open_Ticket

}
// --------------------------------------------------------------------------------------------------------------
public function replyTicket() {
 	// Support Tickets - http://wiki.whmcs.com/API:Reply_Ticket

}
// --------------------------------------------------------------------------------------------------------------
public function addTicketNote() {
 	// Support Tickets - http://wiki.whmcs.com/API:Add_Ticket_Note

}
// --------------------------------------------------------------------------------------------------------------
public function deleteTicket() {
 	// Support Tickets - http://wiki.whmcs.com/API:Delete_Ticket

}
// --------------------------------------------------------------------------------------------------------------

// ========================================================================================================================
// Quotes
// ========================================================================================================================
 
// --------------------------------------------------------------------------------------------------------------
public function createQuote() {
 	// Payments/Billing - http://wiki.whmcs.com/API:Create_Quote

}
// --------------------------------------------------------------------------------------------------------------
public function updateQuote() {
 	// Payments/Billing - http://wiki.whmcs.com/API:Update_Quote

}
// --------------------------------------------------------------------------------------------------------------
public function deleteQuote() {
 	// Payments/Billing - http://wiki.whmcs.com/API:Delete_Quote

}
// --------------------------------------------------------------------------------------------------------------
public function sendQuote() {
 	// Payments/Billing - http://wiki.whmcs.com/API:Send_Quote

}
// --------------------------------------------------------------------------------------------------------------
public function acceptQuote() {
 	// Payments/Billing - http://wiki.whmcs.com/API:Accept_Quote 

}
// --------------------------------------------------------------------------------------------------------------


// ________________________
} // end class