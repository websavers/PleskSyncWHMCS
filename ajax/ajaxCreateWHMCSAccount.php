<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync for WHMCS :: ajaxCreateWHMCSAccount.php
// ------------------------------------------------------------------------------------------------------------

ob_implicit_flush(TRUE);  // turn off buffering output

include ("../Whmcs.Api.class.php");
include ("../../../../configuration.php");     // whmcs config: database user/pass
set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] );
include ("../config.php");                    // plesksync config

$bNoEmail = (($_GET["sendemail"] == "true") ? 'false' : 'true');

header('Content-Type: text/html; charset=UTF-8');   // required for ajax output


    // *** using WHMCS API Wrapper class
      
    $whmcsObj = new WhmcsApiWrapper();
    
    $whmcsObj->setApiUrl($whmcs_api_url);           // path to http://yoursite.com/whmcs_dir/includes/api.php
    
    
    if (isset($whmcs_api_username))     
    
              $whmcsObj->setLoginAccess($whmcs_api_username, $whmcs_api_password);                  // use override entries in config.php
    else                              
              $whmcsObj->setLoginAccessAutomatic($db_username,$db_password, $db_name, $db_host);    // no entries in config.php: try to auto-detect
  
  
    echo '&rArr; WHMCS API: AddClient...<br />';   


        $iResult = $whmcsObj->addClient($_GET['first'],                                 // first name
                                        $_GET['last'],                                  // last name
                                        $_GET['company'],                               // (optional) company name
                                        $_GET['email'],                                 // e-mail address (also username)
                                        $_GET['password'],                              // plain text password for account
                                        $_GET['address1'],                              // address 1
                                        "",                                             // (optional) address 2 - [*NOT used by Plesk*]
                                        $_GET['city'],                                  // city
                                        $_GET['state'],                                 // state
                                        $_GET['postcode'],                              // postcode
                                        $_GET['country'],                               // two-letter ISO country code
                                        $_GET['phone'],                                 // telephone number (numbers, dashes and spaces only!)
                                        $bNoEmail,                                      // pass as true to surpress the client signup welcome email sending
                                        "1",                                            // default currency                                        
                                        '*** Imported via Plesk Sync on (' . date("F j, Y, g:i a") . ')  *** (Please check: Payment Method, Pricing, Billing Cycle & Next Due Date)',      // (optional) admin notes
                                        "",                                     // (optional) group id
                                        "",                                     // (optional) credit card type
                                        "",                                     // (optional) credit card number
                                        "",                                     // (optional) credit card expiration (MMYY)
                                        "",                                     // (optional) start date   
                                        "",                                     // (optional) issue number
                                        ""                                      // (optional) custom fields: a base64 encoded serialized array of custom field values,  ex: base64_encode(serialize(array("1"=>"Google"))); 
                                        );

                if ($iResult) {
                   
                            $objData = $whmcsObj->getTransactionData();        
                            $iClientId = $objData["clientid"];
                            
                                        echo '<span style="color:green"><br />&#10004; Added new client:</span><br /><br />&bull; Name: '. $_GET['first'] . ' ' . $_GET['last'] . '<br />&bull; Id: <a href="clientssummary.php?userid=' . $results["clientid"] . '" target="_blank">#' . $results["clientid"] . '</a>.<br />';
                                        if (!$bNoEmail) echo '&raquo; Welcome e-mail sent.<br />';
                                        
                } else {                        
                            $objData = $whmcsObj->getTransactionData();
                            
                                        echo '<span style="color:red">x WHMCS API Error: ' . $objData["errormessage"] . '<br />';
            
                }



       
       
?>