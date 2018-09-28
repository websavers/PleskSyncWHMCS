<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync for WHMCS :: ajaxChangeStatusInPlesk.php
// ------------------------------------------------------------------------------------------------------------

error_reporting(E_ALL); 
$display_errors = true;
ob_implicit_flush(TRUE);  // turn off buffering output

// ------------------------------------------------------------------------------------------------------------
class ApiRequestException extends Exception {}
// ------------------------------------------------------------------------------------------------------------
function CreateXMLPacketToSend($client_id, $domain_id, $suspend = 0) {

      $client_status = $suspend=="1" ? '16' : '0';   // 16 = client suspended
      $domain_status = $suspend=="1" ? '2' : '0';
      
      if ($suspend == "1") echo '&rArr; Sent command to <i>suspend</i> the status of domain.<br />';
      else echo '&rArr; Sent command to <i>unsuspend</i> the status of domain.<br />';
      
       $strPacket = '<packet>';
       $strPacket .= '<client><set><filter><id>' . $client_id . '</id></filter><values><gen_info><status>' . $client_status . '</status></gen_info></values></set></client>';
       $strPacket .= '<domain><set><filter><id>' . $domain_id . '</id></filter><values><gen_setup><status>' . $domain_status . '</status></gen_setup></values></set></domain>';
       $strPacket .= '</packet>';      

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
      curl_setopt($curl, CURLOPT_HTTPHEADER,array("HTTP_AUTH_LOGIN: {$login}","HTTP_AUTH_PASSWD: {$password}","HTTP_PRETTY_PRINT: TRUE", "Content-Type: text/xml"));

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
// ----------------------------
function parseResponse($response_string) {

        if (isset($response_string)) {
	    
	    $xml = new SimpleXMLElement($response_string);
      
	    if (!is_a($xml, 'SimpleXMLElement')) throw new ApiRequestException("Cannot parse server response: {$response_string}");
      
	    return $xml;
      
         } else  throw new ApiRequestException("Invalid server response: error communicating. parseResponse()");      
}
// ----------------------------
function checkResponse(SimpleXMLElement $response) {

        if (isset($response)) {
	    
		  $resultNode = $response->xpath('//*[name()="system"]');
		  if (!$resultNode) $resultNode = $response->xpath('//*[name()="result"]');
	    
		  if ((string)$resultNode[0]->status == "error") {
			       $strErrorText = (string)$resultNode[0]->errtext;
			       $iErrorCode = (string)$resultNode[0]->errcode;
			       echo '<br />x <span style="color:red">Error: '.$iErrorCode. ' - '. $strErrorText;
			
		  } else 
			       echo '<div style="color:green">&#10004; Success!</div>';
			       
        } else  throw new ApiRequestException("Invalid server response: checkResponse()");			       
            
}

// ------------------------------------------------------------------------------------------------------------
//// int main()
// ------------------------------------------------------------------------------------------------------------

header('Content-Type: text/html; charset=UTF-8');   // required for ajax output

echo '<div style="background-color:#fff;border: 1px solid yellow;padding:3px">';



$curl = curlInit($_GET['ip'], $_GET['l'], urldecode($_GET['p']), $_GET['secure']);

try {
try {

      echo '<span style="font-size:7pt;color:black">';
      echo "&rArr; Connecting to Plesk server (".$_GET['ip'].")...<br />";
      
      $response = sendRequest($curl, CreateXMLPacketToSend($_GET['cid'],$_GET['did'],$_GET['suspend'])->saveXML());
      
      $responseXml = parseResponse($response);

      checkResponse($responseXml);

      echo '</span>';
      
} catch (ApiRequestException $e) { echo $e; die(); }
} catch (Exception $e) { echo $e; die(); }

// ------------------------------------------------------------------------------------------------------------

?>