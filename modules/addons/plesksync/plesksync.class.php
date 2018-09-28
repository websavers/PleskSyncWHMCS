<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync :: plesksync.class.php
// ------------------------------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------------------------------
class ApiRequestException extends Exception {}
// ------------------------------------------------------------------------------------------------------------
function createPagedDomainsDocument($iFrom, $iTo) {

        $strPacket = '<packet><webspace><get><filter>';
        $i = $iFrom;
        while ($i <= $iTo) {
            $strPacket .= '<id>'.$i++.'</id>';   
        }
        //$strPacket .= '</filter><dataset><user/><gen_info/><stat/></dataset></get></webspace></packet>';
        $strPacket .= '</filter><dataset><gen_info/><stat/></dataset></get></webspace></packet>';

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
      
    
      return $xmldoc;
}
// ------------------------------------------------------------------------------------------------------------
function curlInit($host, $login, $password, $secure) {

      if ($secure == "on") $strURLprefix = "https";
      else $strURLprefix = "http";
           
      $curl = curl_init();

      curl_setopt($curl, CURLOPT_URL, $strURLprefix."://".$host.":8443/enterprise/control/agent.php");
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST,           true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
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
              
             $xml = new SimpleXMLElement($response_string);
       
             if (!is_a($xml, 'SimpleXMLElement')) throw new ApiRequestException("Cannot parse server response: {$response_string}");
       
             return $xml;
       
         } else  throw new ApiRequestException("Invalid server response: error communicating. parseResponse()");
        
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