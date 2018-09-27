<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync for WHMCS :: ajaxGetPleskAccountDetails.php
// ------------------------------------------------------------------------------------------------------------

ob_implicit_flush(TRUE);  // turn off buffering output


// ------------------------------------------------------------------------------------------------------------
class ApiRequestException extends Exception {}
// ------------------------------------------------------------------------------------------------------------
function bytesToSize1024($bytes = 0, $precision = 2) {
        
	$unit = array('B','KB','MB','GB','TB','PB','EB');
	return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
}
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
					 echo '<span style="color:red">Plesk API returned error: '.$iErrorCode. ' - '. $strErrorText . '</span>';
			    }
			    
        } else  throw new ApiRequestException("Invalid server response: checkResponse()");			    
}
// ------------------------------------------------------------------------------------------------------------
//// int main()
// ------------------------------------------------------------------------------------------------------------

header('Content-Type: text/html; charset=UTF-8');   // required for ajax output


$curl = curlInit($_GET['ip'],  $_GET['l'], urldecode($_GET['p']), $_GET['secure']);

try {
try { 
      $response = sendRequest($curl, CreateXMLPacketToSend($_GET['did'])->saveXML());
            
      $responseXml = parseResponse($response);

      checkResponse($responseXml);
             
      $userNode = $responseXml->xpath('//*[name()="gen_info"]');
      

			     echo '<span style="font-size:8pt;color:black">';
			      echo '&bull; Name: <b>' . (string)$userNode[0]->pname . '</b><br />';		
			      echo '&bull; Company: <b>' . (string)$userNode[0]->cname . '</b><br />';
			      echo '&bull; Phone: <b>' . (string)$userNode[0]->phone . '</b><br />';
			      if ((string)$userNode[0]->fax) echo '&bull; Fax: <b>' . (string)$userNode[0]->fax . '</b><br />';
			      echo '&bull; E-mail: <b>' . (string)$userNode[0]->email . '</b><br />';
			      echo '&bull; Address:  <b>' . (string)$userNode[0]->address . '</b><br />';
			      echo '&bull; City: <b>' . (string)$userNode[0]->city . '</b><br />';
			      echo '&bull; State: <b>' . (string)$userNode[0]->state . '</b><br />';
			      echo '&bull; Postcode:  <b>' . (string)$userNode[0]->postcode . '</b><br />';
			      echo '&bull; Country:  <b>' . (string)$userNode[0]->country . '</b><br />';
			      echo '&bull; Login: <b>' . (string)$userNode[0]->login . '</b><br />';      
			      echo '&bull; Password:  <b>' . (string)$userNode[0]->password . '</b>';     
			      echo '<br />';

				    // status = Allowed values: 0 (active) | 16 (disabled_by admin) | 4 (under backup/restore) | 256 (expired)
			      echo '&bull; Status: ';
					switch ((string)$userNode[0]->status) {
					   case "0": echo '<span style="color:green">active</span>';
						   break;
					   case "16": echo '<span style="color:red">disabled by admin (suspended)</span>';
						   break;	
					   case "4": echo '<span style="color:red">under backup/restore</span>';
						   break;
					   case "256": echo '<span style="color:red">expired</span>';
						   break;	
					   default: echo '<span style="color:black">'.(string)$userNode[0]->status.'</span><br />';
						   break;	
					}
			   				

      echo '</span></div>';
      
} catch (ApiRequestException $e) { echo $e; die(); }
} catch (Exception $e) { echo $e; die(); }

// ------------------------------------------------------------------------------------------------------------

?>