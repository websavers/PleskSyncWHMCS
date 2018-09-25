<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync for WHMCS :: ajaxCreateWHMCSOrder.php
// ------------------------------------------------------------------------------------------------------------

ob_implicit_flush(TRUE);  // turn off buffering output
    
include ("../Whmcs.Api.class.php");
include ("../../../../configuration.php");     // whmcs config: database user/pass
set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] );
include ("../config.php");                    // plesksync config


$bNoEmail = (($_GET["sendemail"] == "true") ? 'false' : 'true');
$bNoInvoice = (($_GET["createinvoice"] == "true") ? 'false' : 'true');

header('Content-Type: text/html; charset=UTF-8');   // required for ajax output

    
    // *** using WHMCS API Wrapper class
    
    $whmcsObj = new WhmcsApiWrapper();
    
    $whmcsObj->setApiUrl($whmcs_api_url);           // path to http://yoursite.com/whmcs_dir/includes/api.php
    
    
    if (isset($whmcs_api_username))     
    
              $whmcsObj->setLoginAccess($whmcs_api_username, $whmcs_api_password);                  // use override entries in config.php
    else                              
              $whmcsObj->setLoginAccessAutomatic($db_username,$db_password, $db_name, $db_host);    // no entries in config.php: try to auto-detect
  
  
// WHMCS API :: AddOrder
// -----------------------------------------------------------------------------------------------------

    echo '<span style="color:black">&rArr; WHMCS API: AddOrder...<br />';

        $iResult = $whmcsObj->addOrder($_GET["domain"],                         // domain name
                                       $_GET["clientid"],                       // client id for to attach order to
                                       $_GET["packageid"],                      // product id
                                       $whmcs_addorder_payment,                 // paypal, authorize, webmoney etc...
                                       $whmcs_addorder_billingcycle,            // onetime, monthly, quarterly, semiannually, etc..
                                       $bNoInvoice,                             // set true to not generate an invoice for this order
                                       $bNoEmail,                               // set true to surpress the order confirmation email
                                        "",                     // (optional) addons - comma seperated list of addon ids
                                        "",                     // (optional) a base64 encoded serialized array of custom field values
                                        "",                     // (optional) a base64 encoded serialized array of configurable product options
                                        "",                     // (optional) set only for domain registration - register or transfer
                                        "",                     // (optional) set only for domain registration - 1,2,3,etc..
                                        "",                     // (optional) set only for domain registration - true to enable
                                        "",                     // (optional) set only for domain registration - true to enable	
                                        "",                     // (optional) set only for domain registration - true to enable	
                                        "",                     // (optional) eppcode - set only for domain transfer
                                        "",                     // (optional) set only for domain registration - DNS Nameserver #1
                                        "",                     // (optional) set only for domain registration - DNS Nameserver #2
                                        "",                     // (optional) set only for domain registration - DNS Nameserver #3
                                        "",                     // (optional) set only for domain registration - DNS Nameserver #4	
                                        "",                     // (optional) pass coupon code to apply to the order (optional)	
                                        "",                     // (optional) affiliate ID if you want to assign the order to an affiliate (optional)
                                        ""                      // (optional) can be used to pass the customers IP (optional)
                                      );
                                        
 	        

                if ($iResult) {         // operation was successful
                   
                            $objData = $whmcsObj->getTransactionData();
                            
                            $iOrderId = $objData["orderid"];
                            $iInvoiceId = $objData["invoiceid"];      // sometimes empty
                            $iProductIds = $objData["productids"];
                      
                                        echo '<br /><span style="color:green;"> &#10004; Successfully added!</span><br /><br />';
                                        echo '&bull; Client: #' . $_GET["clientid"] .' <br />&bull; Hosting: ' . $_GET["domain"] . '<br />&bull; Package: #' . $_GET["packageid"] .' <br /><br />';
                                        echo '&raquo; Order Id #' . $iOrderId . '<br />';
                                        
                                        if (!empty($iInvoiceId)) echo '&raquo;  <a href="invoices.php?action=edit&id=' . $iInvoiceId . '" target="_blank">Invoice #' . $iInvoiceId . '</a><br />';
                                        
                                        echo '&raquo; <a href="clientshosting.php?userid=' . $_GET["clientid"] . '&id=' . $iProductIds . '">Product Id # ' . $iProductIds . '</a><br />';
                                        
                                        if (!$bNoInvoice) echo '&raquo; Invoice was generated.<br />';
                                        if (!$bNoEmail) echo '&raquo; Order confirmation e-mail sent.<br />';
              

// WHMCS API :: EncryptPassword
// -----------------------------------------------------------------------------------------------------            
                                                  if (isset($_GET["client_login"]) && isset($_GET["client_password"])) {       // need to use WHMCS API to encrypt the password first!
                  
                                                            echo '<br /><span style="color:black">&rArr; WHMCS API: EncryptPassword...</span>';
                                                            
          
                                                            $iResult = $whmcsObj->encryptPassword(urldecode($_GET["client_password"]));          
                                                            
                                                            if ($iResult) {         // operation was successful
                                                               
                                                                        $objData = $whmcsObj->getTransactionData();
                                                                      
                                                                        $whmcsEncryptedPassword = $objData["password"];
                                                                        echo '<span style="color:green;"> &#10004;</span> ';
                                                                        
                                                            } else {                        
                                                                        $objData = $whmcsObj->getTransactionData();    
                                                                 
                                                                        echo '<br /><div style="color:red">x WHMCS API Error: ' . $objData["errormessage"] . '</div>';
                                                                      
                                                                        continue;  // *** continue
                                                            }
                                                  
     
                                                            // manually change the username and password in the database
                                                            
                                                            echo '<br /><span style="color:black">&rArr; Setting the login & password for hosting...<br />';
                                                            
                                                                      $sqlconnection = mysql_connect($db_host,$db_username,$db_password);											  					
                                                                      mysql_select_db($db_name);					
                                                                                                                                                      
                                                                      $queryUpdateResult = mysql_query("UPDATE `tblhosting` SET `username` = '" .$_GET["client_login"] . "', `password` = '" . $whmcsEncryptedPassword . "' WHERE `id` = ". $iProductIds , $sqlconnection);
                                                                      
                                                                      mysql_close($sqlconnection);
                                                            
                                                  } else '<div style="color:red">&rArr; Note: Username & password are blank.</div>';                        
                                              
                } else {                        
                            $objData = $whmcsObj->getTransactionData();
                            
                            echo '<div style="color:red">x WHMCS API Error: ' . $objData["errormessage"] . '</div>';
                            
                            exit;                // # quit.
                }                  




// WHMCS API :: AcceptOrder
// -----------------------------------------------------------------------------------------------------
         
              echo '<span style="color:black">&rArr; WHMCS API: AcceptOrder...<br />';
              

                    $iResult = $whmcsObj->acceptOrder($iOrderId);
                                                    
                            if ($iResult) {         // operation was successful
                               
                                        $objData = $whmcsObj->getTransactionData();
        
                                                  echo '<span style="color:green">&#10004; Order was accepted!</span><br /><br />';
                                                 
                                                  echo '&rArr; <strong>Please <a href="clientshosting.php?userid=' . $_GET["clientid"] . '&id=' . $results["productids"] . '">go to product profile</a> and verify:<br />&bull; Payment Method<br />&bull; Pricing<br />&bull; Billing Cycle<br />&bull; Next Due Date</strong><br />';
                                                                                            
                                        
                            } else {                        
                                        $objData = $whmcsObj->getTransactionData();    
                            
                                                  echo '<div style="color:red">x Order # [' . $iOrderId . '] was not accepted!</div><br />';
                         
                                                  echo '<div style="color:red">x WHMCS API Error: ' . $objData["errormessage"] . '</div>';                                    
                            }





                    
    
?>