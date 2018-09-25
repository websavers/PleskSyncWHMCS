<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync :: ajaxImportPleskAccount.php
// ------------------------------------------------------------------------------------------------------------
error_reporting(E_ALL); 
$display_errors = true;
ob_implicit_flush(TRUE);  // turn off buffering output


include ("../../../../configuration.php");    // whmcs config: database user/pass



// ------------------------------------------------------------------------------------------------------------
class ApiRequestException extends Exception {}
// ------------------------------------------------------------------------------------------------------------
function CreateXMLPacketToSend($domain_id) {

      $strPacket = '<packet version="1.4.1.2">';
      $strPacket .= '<client><get><filter><id>' . $domain_id . '</id></filter><dataset><gen_info/><permissions/></dataset></get></client></packet>';
      
      $xmlDomDoc = new DomDocument('1.0', 'UTF-8');
      $xmlDomDoc->formatOutput = true;
      $xmlDomDoc->loadXML($strPacket);

return $xmlDomDoc;
}
// ----------------------------
function curlInit($host, $login, $password, $secure) {

      $strURLprefix = "https";
      if ($secure == "1") $strURLprefix = "https";
      else $strURLprefix = "https";
           
      $curl = curl_init();

      curl_setopt($curl, CURLOPT_URL, $strURLprefix."://".$host.":8443/enterprise/control/agent.php");
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST,           true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array("HTTP_AUTH_LOGIN: {$login}","HTTP_AUTH_PASSWD: {$password}","HTTP_PRETTY_PRINT: TRUE","Content-Type: text/xml"));

      return $curl;
}
// ----------------------------
function sendRequest($curl, $packet) {

      curl_setopt($curl, CURLOPT_POSTFIELDS, $packet);

      $result = curl_exec($curl);

      if (curl_errno($curl)) {
             $errmsg  = curl_error($curl);
             $errcode = curl_errno($curl);
             curl_close($curl);
             throw new ApiRequestException($errmsg, $errcode);
      }

      curl_close($curl);

      return $result;
}
// ------------------------------------------------------------------------------------------------------------
function parseResponse($response_string) {

        if (isset($response_string)) {
	    
		  $xml = new SimpleXMLElement($response_string);
	    
		  if (!is_a($xml, 'SimpleXMLElement')) throw new ApiRequestException("Cannot parse server response: {$response_string}");
	    
		  return $xml;
	    
         } else  throw new ApiRequestException("Invalid server response: error communicating. parseResponse()");
	 
}
// ------------------------------------------------------------------------------------------------------------
function checkResponse(SimpleXMLElement $response) {

        if (isset($response)) {
		
		  $resultNode = $response->xpath('//*[name()="system"]');    // login error and such return 'system' schema
		  
		  if (!$resultNode) $resultNode = $response->xpath('//*[name()="result"]');  // general search for 'result' schema
	    
			      if ((string)$resultNode[0]->status == "error") {
				    
					   $strErrorText = (string)$resultNode[0]->errtext;
					   $iErrorCode = (string)$resultNode[0]->errcode;
					   echo '<span style="color:red">Plesk API returned error: '.$iErrorCode. ' - '. $strErrorText;
			      }
			      
        } else  throw new ApiRequestException("Invalid server response: checkResponse()");			      
}
// ------------------------------------------------------------------------------------------------------------
//// int main()
// ------------------------------------------------------------------------------------------------------------

header('Content-Type: text/html; charset=UTF-8');   // required for ajax output

echo '<div style="background-color:#fff;border: 1px solid yellow;padding:3px">';


$iDomainId  =  $_GET['did'];


$curl = curlInit($_GET['ip'],  $_GET['l'], urldecode($_GET['p']), $_GET['secure']);

try {		 
      try {

      $response = sendRequest($curl, CreateXMLPacketToSend($iDomainId)->saveXML());
            
      $responseXml = parseResponse($response);
     
      checkResponse($responseXml);
             
      $userNode = $responseXml->xpath('//*[name()="gen_info"]');
      
   
      	    	        // first, try to find this account through email address: this has to be done here
		
			      if ($strEmail = (string)$userNode[0]->email) {
							   
					  $con1 = mysql_connect("localhost",$db_username,$db_password);											  					
					  mysql_select_db($db_name);					
															  
					  $result = mysql_query("SELECT * FROM `tblclients` WHERE `email` = '" .  $strEmail ."'", $con1);			    
					  $row = mysql_fetch_array($result);
					  
					  if ($row['id']) {
						      
						      echo '<div id="addorderimportOut' .  $iDomainId  . '">';
						      echo '<span style="color:green;font-weight:bold;font-size:8pt;">&#10004; User already exists in WHMCS!</span><br /><br />';
						      echo '<div style="color:black;font-size:7pt">&bull; ' .  $strEmail .'<br />&bull; <a href="clientssummary.php?userid=' . $row['id'] . '" target="_blank">'. utf8_decode($row['firstname']) . ' '. utf8_decode($row['lastname']) . '</a><br />&bull; Id: #' . $row['id'] . '</div><br />';
					
						  
						      echo '<strong>Choose Package:</strong> <select name="packageid' . $iDomainId . '" id="packageid' . $iDomainId . '" style="font-size:7pt;color:black;">';
						      
						      // get list of packages
						      $resultProducts = mysql_query("SELECT name, id FROM `tblproducts` WHERE `servertype` = 'plesk' ORDER BY `name` ASC", $con1);			    						      
						      while($rowProducts = mysql_fetch_array($resultProducts)) echo '<option value="' . $rowProducts['id'] . '">' . $rowProducts['name'] . '</option>';
			
						      echo '</select><br /><br /><input type="checkbox" id="createinvoice' . $iDomainId . '" name="createinvoice' . $iDomainId . '" style="font-size:6pt;color:black;"><label for="createinvoice' . $iDomainId . '">Generate invoice?</label><br />';   // checked="checked"
						      
						      echo '<br /><input type="checkbox" id="sendemail' . $iDomainId . '" name="sendemail' . $iDomainId . '" style="font-size:6pt;color:black;"><label for="sendinvoice' . $iDomainId . '">Send Order confirmation e-mail?</label><br />';  
				
						      echo '<br /><center><input type="button" value="Attach Order to Client #' . $row['id'] . '"  id="addorderimportBtn' . $iDomainId . '" ';
						      echo 'onClick="CreateWHMCSOrder(\'addorderimportOut' . $iDomainId . '\',\'clientid=' . $row['id'];
						      echo '&domain=' . $_GET['domain'] . '&client_login=' . ((string)$userNode[0]->login) . '&client_password=' . urlencode(((string)$userNode[0]->password)) . '\',\'packageid'. $iDomainId . '\',\'createinvoice'. $iDomainId . '\',\'sendemail' . $iDomainId . '\')"></center></div>';			      
						      
						      exit;  // no import necessary -- show next step and QUIT!!!
					  }
			      }
			
	
			      
			// show form to add a new client in whmcs
			$strFirst = (string)$userNode[0]->pname;
			$strFirst = substr($strFirst,0,strpos($strFirst, " ")); 
			$strLast = (string)$userNode[0]->pname;
			$strLast = substr($strLast,strpos($strLast, " ")+1,strrpos($strLast, " "));
			
			echo '<div id="createaccount_output' . $iDomainId . '"><span style="font-size:8pt;color:black"> ';
			
			echo 'First: <input type="text" style="font-size:7pt;color:black;"  id="pleskFirst' . $iDomainId  . '" size="21" value="' . ucfirst($strFirst) . '"/>' . ' <br />';
			echo 'Last: <input type="text" style="font-size:7pt;color:black;"  id="pleskLast' . $iDomainId  . '" size="21" value="' . $strLast . '"/>' . ' <br />';			    
			echo 'Company: <input type="text" style="font-size:7pt;color:black;"  id="pleskCompany' . $iDomainId  . '" size="21" value="' . (string)$userNode[0]->cname . '"/>' . ' <br />';
			echo 'Phone: <input type="text" style="font-size:7pt;color:black;"  id="pleskPhone' . $iDomainId  . '" size="10" value="' . (string)$userNode[0]->phone . '"/>' . ' <br />';		
			echo 'E-mail: <input type="text" style="font-size:7pt;color:black;" id="pleskEmail' . $iDomainId  . '" size="20" value="' . (string)$userNode[0]->email . '"/>' . ' <br />';
			echo 'Address:  <input type="text" style="font-size:7pt;color:black;" id="pleskAddress' . $iDomainId  . '" size="20" value="' . (string)$userNode[0]->address . '"/>' . ' <br />';
			echo 'City: <input type="text" style="font-size:7pt;color:black;" id="pleskCity' . $iDomainId  . '" size="12" value="' . (string)$userNode[0]->city . '"/>' . ' <br />';
			echo 'State:  <input type="text" style="font-size:7pt;color:black;" id="pleskState' . $iDomainId  . '"  size="5" value="' . (string)$userNode[0]->state . '"/>' . ' <br />';
			echo 'Postcode:  <input type="text" style="font-size:7pt;color:black;" id="pleskPostcode' . $iDomainId  . '" size="6" value="' . (string)$userNode[0]->pcode . '"/>' . ' <br />';
			echo 'Country:  <input type="text" style="font-size:7pt;color:black;"  id="pleskCountry' . $iDomainId  . '" size="3" value="' . (string)$userNode[0]->country . '"/>' . ' <br />';
			echo 'Login:  <input type="text" style="font-size:7pt;color:black;" id="pleskLogin' . $iDomainId  . '" size="15" value="' . (string)$userNode[0]->login . '"/>' . ' <br />';      
			echo 'Password:  <input type="text" style="font-size:7pt;color:black;"  id="pleskPassword' . $iDomainId  . '" size="15" value="' . (string)$userNode[0]->password . '"/>' . ' <br />';
			  
			echo '<br /><input type="checkbox" id="sendemail' . $iDomainId  . '" style="font-size:7pt;color:black;"><label for="sendemail' . $iDomainId  . '">Send welcome e-mail message?</label><br />';  //checked="checked"
			  
			echo '<center><br /><span style="font-size:7pt;color:red">(Verify the details above are correct.)</span><br /><br /><input type="button" value="Create new account in WHMCS &raquo;"  id="createaccount' .$iDomainId. '" ';
			echo 'style="color:green" onClick="CreateWHMCSAccount(\'createaccount_output'.$iDomainId. '\',\'did=' . $iDomainId  . '\',\'pleskFirst' . $iDomainId  . '\', \'pleskLast' . $iDomainId  . '\', \'pleskCompany' . $iDomainId  . '\',';
			echo '\'pleskEmail' . $iDomainId  . '\', \'pleskPhone' . $iDomainId  . '\',  \'pleskAddress' . $iDomainId  . '\', \'pleskCity' . $iDomainId  . '\', \'pleskState' . $iDomainId  . '\', \'pleskPostcode' . $iDomainId  . '\',';
			echo '\'pleskCountry' . $iDomainId  . '\', \'pleskLogin' . $iDomainId  . '\', \'pleskPassword' . $iDomainId  . '\',\'sendemail' . $iDomainId . '\')"></form></center><br />';   
		  
			echo '</span></div>';
      
      } catch (ApiRequestException $e) { echo $e; die(); }
} catch (Exception $e) { echo $e; die(); }
      

// ------------------------------------------------------------------------------------------------------------

?>