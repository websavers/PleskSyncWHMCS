<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync :: plesksync.class.php
// ------------------------------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------------------------------
class ApiRequestException extends Exception {}
  
function pleskGetServicePlans($curl){
  $strPacket = '<packet><service-plan><get><filter/></get></service-plan></packet>';
  
  $response = sendRequest($curl, createPacket($strPacket));
  $responseXml = parseResponse($response);
  checkResponse($responseXml);
  
  return $responseXml->xpath('//*[name()="result"]');
}
  
function pleskGetCustomers($curl, $filtertype, $ids){
  
  $strPacket = '<packet><customer><get><filter>';
  
  if (is_array($ids)){ //multiple
    foreach ($ids as $id){
      $id = (int) $id;
      $strPacket .= "<$filtertype>$id</$filtertype>";
    }
  }
  else{ //single
    $ids = (int) $ids;
    $strPacket .= "<$filtertype>$ids</$filtertype>";
  }
  //$strPacket .= '</filter><dataset><gen_info/><stat/></dataset></get></customer></packet>';
  $strPacket .= '</filter><dataset><gen_info/></dataset></get></customer></packet>';
  
  $response = sendRequest($curl, createPacket($strPacket));
  $responseXml = parseResponse($response);
  checkResponse($responseXml);
  
  return $responseXml->xpath('//*[name()="gen_info"]');
  
}
// ------------------------------------------------------------------------------------------------------------
function createPagedDomainsDocument($iFrom, $iTo) {

        $strPacket = '<packet><webspace><get><filter>';
        $i = $iFrom;
        while ($i <= $iTo) {
            $strPacket .= '<id>'.$i++.'</id>';   
        }
        //$strPacket .= '</filter><dataset><user/><gen_info/><stat/></dataset></get></webspace></packet>';
        $strPacket .= '</filter><dataset><gen_info/><stat/><subscriptions/></dataset></get></webspace></packet>';

return createPacket($strPacket);
}
// ------------------------------------------------------------------------------------------------------------
function createPacket($xmlstring) {
  
  $xmlDomDoc = new DomDocument('1.0', 'UTF-8');    
  $xmlDomDoc->formatOutput = true;
  $xmlDomDoc->loadXML($xmlstring);

return $xmlDomDoc->saveXML();
}
// ------------------------------------------------------------------------------------------------------------
function createAllDomainsDocument() {

      $xmldoc = new DomDocument('1.0', 'UTF-8');
      $xmldoc->formatOutput = true;

      $packet = $xmldoc->createElement('packet');
      $xmldoc->appendChild($packet);

      // <packet/webspace>
      $webspace = $xmldoc->createElement('webspace');
      $packet->appendChild($webspace);

      // <packet/webspace/get>
      $get = $xmldoc->createElement('get');
      $webspace->appendChild($get);

      // <packet/webspace/get/filter>
      $filter = $xmldoc->createElement('filter');  
      $get->appendChild($filter);

      // <packet/webspace/get/dataset>
      $dataset = $xmldoc->createElement('dataset');
      $get->appendChild($dataset);

      // dataset elements
      //$dataset->appendChild($xmldoc->createElement('user'));
      $dataset->appendChild($xmldoc->createElement('gen_info'));
      $dataset->appendChild($xmldoc->createElement('stat'));
      $dataset->appendChild($xmldoc->createElement('subscriptions'));
      
    
      return $xmldoc;
}
// ------------------------------------------------------------------------------------------------------------
function curlInit($host, $login, $password) {
           
      $curl = curl_init();

      curl_setopt($curl, CURLOPT_URL, "https://".$host.":8443/enterprise/control/agent.php");
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST,           true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); //302 is provided upon error. Need to follow it to get XML result.
      curl_setopt($curl, CURLOPT_HTTPHEADER, array("HTTP_AUTH_LOGIN: {$login}","HTTP_AUTH_PASSWD: {$password}","HTTP_PRETTY_PRINT: TRUE","Content-Type: text/xml"));

      return $curl;
}
// ------------------------------------------------------------------------------------------------------------
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
      
      logModuleCall('plesksync', 'sendRequest', (string)$packet, (string)$result, $processedData = "", $replaceVars = "");

      return $result;
}
// ------------------------------------------------------------------------------------------------------------
function parseResponse($response_string) {

    if (isset($response_string)) {
      
      try{
        $xml = new SimpleXMLElement($response_string);
        if (!is_a($xml, 'SimpleXMLElement')) throw new ApiRequestException("Cannot parse server response: {$response_string}");
        return $xml;
      } catch (Exception $e) {
        echo 'Caught exception while parsing XML: ',  $e->getMessage(), "\n";
      }
   
     } 
     else throw new ApiRequestException("No server response: error communicating with server. parseResponse()");
        
}
// ------------------------------------------------------------------------------------------------------------
function checkResponse(SimpleXMLElement $response) {

    if (isset($response)) {
         
      $resultNode = $response->xpath('//*[name()="system"]');    // login error and such return 'system' schema
      if (!$resultNode) $resultNode = $response->xpath('//*[name()="result"]');
       
      if ((string)$resultNode[0]->status == "error") {
     
            $strErrorText = (string)$resultNode[0]->errtext;
            $iErrorCode = (string)$resultNode[0]->errcode;
            echo '<span style="color:red">Plesk API returned error: '.$iErrorCode. ' - '. $strErrorText;
      }
            
    } else  throw new ApiRequestException("Invalid server response: checkResponse()");
 
}
// ------------------------------------------------------------------------------------------------------------

?>