<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync for WHMCS :: ajaxGetServerStats.php
// ------------------------------------------------------------------------------------------------------------
error_reporting(E_ALL); 
$display_errors = true;
ob_implicit_flush(TRUE);  // turn off buffering output

// ------------------------------------------------------------------------------------------------------------
class ApiRequestException extends Exception {}
// ------------------------------------------------------------------------------------------------------------
function bytesToSize1024($bytes = 0, $precision = 2) {
        
	$unit = array('B','KB','MB','GB','TB','PB','EB');
	return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
}
// ------------------------------------------------------------------------------------------------------------
function createServerInfoDocument() {

        // Full list of variables returned here: http://download1.parallels.com/Plesk/PPP9/Doc/en-US/plesk-9.2-api-rpc/40174.htm        
        $strPacket = '<packet version="1.4.1.2"><server><get><stat/></get></server></packet>';

        $xmlDomDoc = new DomDocument('1.0', 'UTF-8');
        $xmlDomDoc->formatOutput = true;
        $xmlDomDoc->loadXML($strPacket);

return $xmlDomDoc;
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
		
		$resultNode = $response->system;
		
		if ((string)$resultNode->status == "error") {
			    $strErrorText = (string)$resultNode->errtext;
			    $iErrorCode = (string)$resultNode->errcode;
			     die ('<span style="color:red">Error: '.$iErrorCode. ' - '. $strErrorText);
		     
		}
		
        } else  throw new ApiRequestException("Invalid server response: checkResponse()");
}

// ------------------------------------------------------------------------------------------------------------
//// int main()
// ------------------------------------------------------------------------------------------------------------

header('Content-Type: text/html; charset=UTF-8');   // required for ajax output


$strPleskServerURLWithPort = "https://".$_GET['ip'].":8443";

$curl = curlInit($_GET['ip'], $_GET['l'], urldecode($_GET['p']), "on");

try {
try {   
      $response = sendRequest($curl, createServerInfoDocument()->saveXML());
      
      $responseXml = parseResponse($response);
         
      checkResponse($responseXml);     
    
      $info = $responseXml->xpath('/packet/server/get/result');
         
      echo '<table border="0" width="100%" cellpadding="1" cellspacing="0">';
      echo '<tr><td style="border-bottom: 1px double grey;background-color: green;color:white">' . '<strong>Plesk v'.(string)$info[0]->stat->version->plesk_version . ' for ' .(string)$info[0]->stat->version->plesk_os . '</strong>'; // ' .(string)$info[0]->stat->version->plesk_os_version . '
     //echo '<tr><td>&bull; ' . 'OS: <strong>'.(string)$info[0]->stat->version->os_release . '</strong>';
     // echo '<tr><td>&bull; ' . 'CPU: <strong>'.(string)$info[0]->stat->other->cpu . '</strong>';
      echo '<tr><td>&bull; ' . 'Uptime: <strong>' .(string)$info[0]->stat->other->uptime . '</strong>';
      echo '<tr><td>&bull; ' . 'Load Averages: <strong>' .(string)$info[0]->stat->load_avg->l1 . ', ' .(string)$info[0]->stat->load_avg->l5 . ', ' .(string)$info[0]->stat->load_avg->l15 . '</strong>';
      echo '<tr><td>&bull; ' . 'Memory: <strong>' . bytesToSize1024($info[0]->stat->mem->used) . ' used, ' . bytesToSize1024($info[0]->stat->mem->free) . ' free, ' .bytesToSize1024($info[0]->stat->mem->total) . ' total.</strong>';
      echo '<tr><td style="border-bottom: 1px solid green;">&bull; ' . 'Disk: <strong>' . (string)$info[0]->stat->diskspace->device->name . ' ' .bytesToSize1024($info[0]->stat->diskspace->device->used) . ' used, ' .bytesToSize1024($info[0]->stat->diskspace->device->free) . ' free, ' . bytesToSize1024($info[0]->stat->diskspace->device->total) . ' total.</strong>';    
      echo '<tr><td>&bull; ' . 'Clients: <strong>' .(string)$info[0]->stat->objects->clients . '</strong>';
      echo '<tr><td style="border-bottom: 1px solid green;">&bull; ' . 'Domains: <strong> ' .(string)$info[0]->stat->objects->active_domains . ' (' .(string)$info[0]->stat->objects->domains . ')</strong>';
      echo '<tr><td>&bull; ' . 'Problem Clients: <strong>' .(string)$info[0]->stat->objects->problem_clients . '</strong>';
      echo '<tr><td style="border-bottom: 1px solid green;">&bull; ' . 'Problem Domains: <strong>' .(string)$info[0]->stat->objects->problem_domains . '</strong>';
      echo '<tr><td>&bull; ' . 'Web Users: <strong>' .(string)$info[0]->stat->objects->web_users . '</strong>';
      echo '<tr><td>&bull; ' . 'Databases: <strong>' .(string)$info[0]->stat->objects->databases . '</strong>';
      echo '<tr><td style="border-bottom: 1px solid green;">&bull; ' . 'Database Users: <strong>' .(string)$info[0]->stat->objects->database_users . '</strong>';
      echo '<tr><td>&bull; ' . 'Mailboxes: <strong>' .(string)$info[0]->stat->objects->mail_boxes . '</strong>';          
      echo '<tr><td>&bull; ' . 'Mail Redirects: <strong>' .(string)$info[0]->stat->objects->mail_redirects . '</strong>';
      echo '<tr><td>&bull; ' . 'Mail Groups: <strong>' .(string)$info[0]->stat->objects->mail_groups . '</strong>';         
      echo '<tr><td>&bull; ' . 'Mail Responders: <strong>' .(string)$info[0]->stat->objects->mail_responders . '</strong>';      
      
      
      echo '</table>';
      
} catch (ApiRequestException $e) { echo $e; die(); }
} catch (Exception $e) { echo $e; die(); }

// ------------------------------------------------------------------------------------------------------------

?>