<?php
// ------------------------------------------------------------------------------------------------------------
// Plesk Sync for WHMCS :: plesksyncwhmcs.php
// ------------------------------------------------------------------------------------------------------------
//  
// ------------------------------------------------------------------------------------------------------------

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
include ("plesksync.class.php");  // include the ApiRequestException extension class

use WHMCS\Database\Capsule;

set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] );

function plesksync_config()
{
    return array(
        'name' => 'Plesk Sync for WHMCS', // Display name for your module
        'description' => 'This module enables you to sync your client data from Plesk with WHMCS',
        'author' => 'Websavers Inc.', // Module author name
        'language' => 'english', // Default language
        'version' => '0.9', // Version number
        'fields' => array(
            'accounts_per_page' => array(
                'FriendlyName' => 'Accounts Per Page',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '100',
                'Description' => 'Amount of accounts it searches for on each page',
            ),
            'whmcs_api_url' => array(
                'FriendlyName' => 'WHMCS API URL',
                'Type' => 'text',
                'Size' => '100',
                'Default' => '../../../includes/api.php',
                'Description' => 'If you have moved the API php file, change the location here.',
            ),
            // the yesno field type displays a single checkbox option
            'whmcs_addorder_payment' => array(
                'FriendlyName' => 'Mark Order as Paid By',
                'Type' => 'dropdown',
								'Options' => array(
										'option1' => 'paypal',
										'option2' => 'creditcard',
										'option3' => 'manual',
								),
                'Description' => 'When creating a matching order, use this payment type. Affects renewals',
            ),
            // the dropdown field type renders a select menu of options
            'whmcs_addorder_billingcycle' => array(
                'FriendlyName' => 'Default Billing Cycle when creating orders',
                'Type' => 'dropdown',
                'Options' => array(
                    'option1' => 'Monthly',
                    'option2' => 'Quarterly',
                    'option3' => 'Annually',
										'option4' => 'Biennially',
										'option5' => 'Triennially',
                ),
                'Description' => 'This billing cycle will be used when creating orders.',
            ),
        )
    );
}

function plesksync_output($vars){
	
	// Get module configuration parameters
	$max_id_results_per_page 			= $vars['accounts_per_page'];
	$whmcs_api_url 								= $vars['whmcs_api_url'];
	#$whmcs_api_username 					= 'apiadmin';              # (optional)
	#$whmcs_api_password 					= 'password_here';         # (optional)
	$whmcs_addorder_payment 			= $vars['whmcs_addorder_payment'];
	$whmcs_addorder_billingcycle 	= $vars['whmcs_addorder_billingcycle'];
	
	$strIp = (isset($_POST['ip']) ? $_POST['ip'] : $_GET['ip']);
	
	if (empty($_POST)) {
    
  	// }}---------------------[MAIN() Default Page - Show active Plesk Servers]---------------------------------------------------------

  	// javascript code to launch the ajax code
  	?>
  	<script type="text/javascript">

  	function getServerStats(outputtag, data) {
  		
  	        $('#' + outputtag).html("<img src='../modules/admin/plesksync/images/wait_details.gif'>");
  	                
  		$.ajax({
  			method: 'get',
  			url: '../modules/admin/plesksync/ajax/ajaxGetServerStats.php',
  			data: data,
  			dataType: 'text',
  			success: function (response) {
  				$('#' + outputtag).html(response);
  			}
  		});		
  	}

  	</script>
  	<?php

  	    showHeaderTitleVersion();

  	    echo 'Plesk Sync is an addon module for WHMCS to import, control, create and synchronize client hosting accounts with your Parallel Plesk servers. <br />';
  	 
  	    echo '<div style="padding-left:25px;padding-top:3px">';
  	    echo 'Accounts are color-coded, reports a diagnosis and resolution, contains statistics on disk space and traffic, detailed client profile information and command buttons to resolve.<br /><br />';
  	    
  	    echo "</div><br />";
  	    echo "&rArr; Auto-detecting servers...";
  	    
  	    echo '<h2>Plesk Servers</h2>';

        echo '<div class="tablebg"><table class="datatable" border="0" cellpadding="3" cellspacing="1" width="100%">';
        echo '<tbody><tr><th width="85">#</th><th>Server Name</th><th>Group</th><th width="95">Host (IP) </th><th width="100">Protocol</th><th width="220">Stats</th><th></th></tr>';
      
  		$servers = Capsule::select("SELECT `tblservers`.name, `tblservers`.hostname, `tblservers`.ipaddress, `tblservers`.maxaccounts, `tblservers`.username, `tblservers`.password, `tblservers`.secure, `tblservergroups`.name AS `groupname` FROM `tblservers` LEFT JOIN `tblservergroupsrel` ON `tblservers`.id = `tblservergroupsrel`.serverid LEFT JOIN `tblservergroups` ON `tblservergroupsrel`.groupid = `tblservergroups`.id WHERE type = 'plesk' ");
      
      $i = 0;
      $servers = json_decode(json_encode($servers), true); //convert from obj to array
  		foreach ($servers as $row){  			
        echo '<tr>';
  			
        echo '<td><img src="images/icons/products.png" align="absmiddle"> '.++$i.'.</td>';
        echo '<td style="color:black;font-weight:bold">'.$row['name'].'</td>';
        echo '<td align="center"><i>'.$row['groupname'].'</i></td>';
        echo '<td>'.$row['ipaddress'].'</td>';

  		      echo '<td align="center">';
  		      
  				// connect to each server and get the list of available protocols that it understands
  				try {		
  					try {
  					       $curl = curlInit($row['ipaddress'], $row['username'], decrypt($row['password']), $row['secure']);
  					       $response = sendRequest($curl, createSupportedProtocolsDocument()->saveXML());
  					       $responseXml = parseResponse($response);
  					       
  					       $info = $responseXml->xpath('//proto[last()]');    // detect protocols available
  					       
  					      echo  $strServerProtocolVersion = (string)$info[0];
  					      
  					      if ($strServerProtocolVersion < "1.4.1.2") throw new ApiRequestException("");
  					      else echo '<span style="color:green;font-size:8pt"> &#10004;</span>';
  					       
  					
  				       } catch (ApiRequestException $e) {
  					      echo '<span style="color:red;font-size:7pt"> x</span></td><td style="color:red;"><i>Version not supported</i></td>';
  					      continue;
  				       }
  				} catch (Exception $e) {
  					      echo '<span style="color:red;font-size:7pt"> x</span></td><td style="color:red;"><i>' . $e . '</i></td>';
  					      continue;
  				}		       

  		      echo '</td>';

  		      echo '<td align="center"><div id="serverinfo_output' . $i . '"> <input type="button" value="Server Statistics"  id="serverinfobtn' .$i. '" style="color:orange" ';
  		      echo 'onClick="getServerStats(\'serverinfo_output'.$i. '\',\'ip='.$row['ipaddress'].'&l='.$row['username'].'&p='.urlencode(decrypt($row['password'])).'\')"></div></td>';     
  	  
  	  
  	              echo '<td><form action="'.$modulelink.'" method="post">';	      
  		      echo '<input name="login_name" value="'.$row['username'].'" type="hidden"><input name="passwd" value="'.decrypt($row['password']).'" type="hidden">';
  		      echo '<input name="ip" value="'.$row['ipaddress'].'" type="hidden"><input name="secure" value="'.$row['secure'].'" type="hidden">';
  		      echo '<input value="Browse Accounts..." type="submit"></form>'; 
  	              echo '</td>';
  		      
  	    
  		      
  		      echo '</tr>';
  		      
  		}
  		echo '</tbody></table></div><br />';
  		
  		showFooter();
  		
  	 // ------------------------------------------------------------------------------
	 
	} else {
		
		// ---------------------[MAIN() Process - Show domain accounts from selected server]---------------------------------------------------------	

		// javascript code to launch the ajax code which can unsuspend accounts in Plesk and import from Plesk => WHMCS		
		?>
		<script type="text/javascript">

		function ChangeAccountStatusPlesk(outputtag, data) {

		  $('#' + outputtag).html("<img src='../modules/admin/plesksync/images/wait_details.gif'>");		        
		        
			$.ajax({
				method: 'get',
				url: '../modules/admin/plesksync/ajax/ajaxChangeStatusInPlesk.php',
				data: data,
				dataType: 'text',
				success: function (response) {
					$('#' + outputtag).html(response);
				}
			});	
			
		}

		function GetAccountDetailsPlesk(outputtag, data) {

			
		        $('#' + outputtag).html("<img src='../modules/admin/plesksync/images/wait_details.gif'>");
		        
		        
			$.ajax({
				method: 'get',
				url: '../modules/admin/plesksync/ajax/ajaxGetPleskAccountDetails.php',
				data: data,
				dataType: 'text',
				success: function (response) {
					$('#' + outputtag).html(response);
				}
			});	
			
		}

		function ImportPleskAccount(outputtag, data) {
			
		        $('#' + outputtag).html("<img src='../modules/admin/plesksync/images/wait_import.gif'>");
		        
		        
			$.ajax({
				method: 'get',
				url: '../modules/admin/plesksync/ajax/ajaxImportPleskAccount.php',
				data: data,
				dataType: 'text',
				success: function (response) {
					$('#' + outputtag).html(response);
				}
			});	
			
		}

		function CreateWHMCSAccount(outputtag, data, first, last, company, email, phone, address1, city, state, postcode, country, login, password, sendemail) {
			   
		            	var strFirst = $('#' + first).val();
		            	var strLast = $('#' + last).val();				
		            	var strCompany = $('#' + company).val();
				var strEmail = $('#' + email).val();
				var strPhone = $('#' + phone).val();
		            	var strAddress1 = $('#' + address1).val();
		            	var strCity = $('#' + city).val();
		            	var strState = $('#' + state).val();
		            	var strPostcode = $('#' + postcode).val();
		            	var strCountry = $('#' + country).val();
		            	var strLogin = $('#' + login).val();
		            	var strPassword = $('#' + password).val();
				var bSendemail = $('#' + sendemail).attr('checked');
		         
			         $('#' + outputtag).html("<img src='../modules/admin/plesksync/images/wait_import.gif'>");
				 
			$.ajax({
				method: 'get',
				url: '../modules/admin/plesksync/ajax/ajaxCreateWHMCSAccount.php',
				data: data + "&first=" + strFirst + "&last=" + strLast + "&company=" + strCompany + "&email=" + strEmail + "&phone=" + strPhone + "&address1=" + strAddress1 + "&city=" + strCity + "&state=" + strState + "&postcode=" + strPostcode + "&country=" + strCountry + "&login=" + strLogin + "&password=" + strPassword + "&sendemail=" + bSendemail,
				dataType: 'text',
				success: function (response) {
					$('#' + outputtag).html(response);
				}
			});	
			
		}

		function CreateWHMCSOrder(outputtag, data, packageid, createinvoice, sendemail) {
			   
		         	var strPackageid = $('#' + packageid).val();
				var bSendemail = $('#' + sendemail).attr('checked');
				var bCreateInvoice = $('#' + createinvoice).attr('checked');

			         $('#' + outputtag).html("<img src='../modules/admin/plesksync/images/wait_import.gif'>");
				 
			$.ajax({
				method: 'get',
				url: '../modules/admin/plesksync/ajax/ajaxCreateWHMCSOrder.php',
				data: data + "&packageid=" + strPackageid + "&sendemail=" + bSendemail + "&createinvoice=" +  bCreateInvoice,
				dataType: 'text',
				success: function (response) {
					$('#' + outputtag).html(response);
				}
			});	
			
		}

		</script>

		<?php

		// --------------------

		$curl = curlInit($strIp, $_POST['login_name'], $_POST['passwd'], $_POST['secure']);

		$iPageNumber = (isset($_POST['page']) ? $_POST['page'] : "0");
		$iMaxResultsPerPage = $max_id_results_per_page;  			// # config.php
		$iStartNumber = ($iPageNumber * $iMaxResultsPerPage) + 1;
		$iEndNumber = ($iPageNumber + 1) * $iMaxResultsPerPage;

		try {
		      showHeaderTitleVersion();

		      echo '<a href="">[ Home ] : Plesk Server Summary</a><br /><br />';

		      echo '<div style="color:grey">&rArr; Connected to Plesk RPC API at ' . $strIp . ':8443/enterprise/control/agent.php...</div><br />';
		     
		      echo '<img src="images/icons/products.png" align="absmiddle"> <span style="color:black;font-weight:bold;font-size:10pt">Plesk Server [' . $strIp . ']</span> <br /><br />';
			   
		      if ($iPageNumber == 'all') {
				echo "&rArr; Downloaded properties of all domains on server.<br />";
				$response = sendRequest($curl, createAllDomainsDocument()->saveXML());
		      } else {
				showNavigationButtons($iPageNumber, $strIp, $_POST['login_name'], $_POST['passwd'], $_POST['secure']);
				
				echo '<div align="center" style="color:black">Domain Accounts with id (<strong>' . $iStartNumber . ' - ' . $iEndNumber . '</strong>)</div>';

				$response = sendRequest($curl, createSomeDomainsDocument($iStartNumber,$iEndNumber)->saveXML());
		      }
		           
		      $responseXml = parseResponse($response);
		   
		      checkResponse($responseXml);

		  
		} catch (ApiRequestException $e) {
		      echo $e; die();
		}


		// Explore the result

		 $iCnt = 0;
		 $iCountMissingFromWHMCS = 0;
		 $iCountSuspendedInPlesk = 0;


			echo '<br /><table border="0" width="1250" cellpadding="1" cellspacing="1"><tr style="border: 1px solid grey;background-color: #f8f5da"><th style="font-size:8pt;">#.</th><th style="font-size:8pt;">ID</th><th style="font-size:8pt;">Domain Name</th>';
      echo '<th style="font-size:8pt;" width="150">Client (Owner)</th><th style="font-size:8pt;" width="80">WHMCS Id</th>';
      echo '<th style="font-size:8pt;" width="70">Created</th><th style="font-size:8pt;">Status</th><th style="font-size:8pt;" width="8">Link</th><th style="font-size:8pt;" width="170">Statistics</th><th style="font-size:8pt;" width="170">Diagnosis</th><th style="font-size:8pt;" width="150">Resolution</th></tr>';

		                                                

		foreach ($responseXml->xpath('/packet/domain/get/result') as $resultNode) {

      if ( (string)$resultNode->status != "ok" ) continue;

      $iClientId = (string)$resultNode->data->gen_info->client_id;
      $iDomainId = (string)$resultNode->id;
      $strDomainName = (string)$resultNode->data->gen_info->name;
      $iDomainStatus = (string)$resultNode->data->gen_info->status;   /// INCORRECT!
      $iAccountStatus = (string)$resultNode->data->gen_info->status;  // 0 = active, 2 = suspended (lack of payment?), 66 = suspended (over limit: disk/bandwidth)
      $strSolutionText ="";
         
      if ($iDomainStatus  != "0") $iCountSuspendedInPlesk++;
           
      // get info from WHMCS
			$plans = Capsule::select("SELECT `tblhosting`.`userid`, `tblhosting`.`id`,  `tblhosting`.`domainstatus`,`tblhosting`.`username`,`tblhosting`.`password`  from `tblhosting` WHERE `tblhosting`.`domain` = '".$strDomainName."' ORDER BY regdate  DESC LIMIT 1");
      $plans = json_decode(json_encode($plans), true); //convert from obj to array
      
      foreach( $plans as $row ){
		      
		      if ($row) {
		                  $iWHMCSClientId = $row['userid'];
		                  $iWHMCSHostingId = $row['id'];
		                  $iWHMCSHostingStatus = $row['domainstatus'];
		                  $bFoundinWHCMS = TRUE;
		      } else {
		                  $iWHMCSClientId = '<span style="color:white;background-color:red;font-size:7pt;font-weight:bold">Not in WHMCS</span>';
		                  $bFoundinWHCMS = FALSE;
		                  $bhasWHMCSDomainAccount = FALSE;
		                  $iCountMissingFromWHMCS++;
		                  $strDomainNotes = "&raquo; NO hosting for this domain in WHMCS";
				  $strSolutionText =  '<div id="createaccntOut' . $iCnt . '"> <input type="button" style="color:green;font-weight:bold" value="Verify & Import &raquo;"  onClick="ImportPleskAccount(\'createaccntOut'.$iCnt. '\',\'did=' . $iClientId  .  '&domain=' . $strDomainName . '&ip='.$strIp .'&l=' . $_POST['login_name'] .'&p='.urlencode($_POST['passwd']).'&secure='.$bSecure .'\')"></div>';

				   
		                        // search for domain name match in WHMCS (Plesk API does not return full users stats unless you request a single domain)
														$domains = Capsule::select("SELECT *  from `tbldomains` WHERE `domain` = '".$strDomainName."' LIMIT 1");
                            $domains = json_decode(json_encode($domains), true); //convert from obj to array
														$rowDomain = $domains[0];
					
		                        if ($rowDomain) {
		                                $iWHMCSDomainId = $rowDomain['id'];
		                                $iWHMCSClientId = $rowDomain['userid'];
		                                $iWHMCSDomainStatus = $rowDomain['status'];
		                                
						$strDomainNotes .= '<br />&raquo; Found possible owner through domain:<br />&bull; <a href="clientssummary.php?userid='. $iWHMCSClientId . '">Client #' .  $iWHMCSClientId. '</a><br />&bull; <a href="clientshosting.php?userid=' .  $iWHMCSClientId  . '&hostingid=' . $iWHMCSHostingId . '">Domain #' . $iWHMCSDomainId . ' (' . $iWHMCSDomainStatus . ')</a>';
		                                
						$strSolutionText = '<div id="addorderOut' . $iCnt . '">';
						$strSolutionText .= '<strong>Choose Package:</strong> <select name="packageid" id="packageid' . $iCnt. '" style="font-size:7pt;color:black;">';
						
						// get list of packages
						$packages = Capsule::select("SELECT name, id FROM `tblproducts` WHERE `servertype` = 'plesk' ORDER BY `name` ASC");
            $packages = json_decode(json_encode($packages), true); //convert from obj to array
						foreach($packages as $rowProducts)  $strSolutionText .= '<option value="' . $rowProducts['id'] . '">' . $rowProducts['name'] . '</option>';
											      
						$strSolutionText .= '</select><br /><br /><input type="checkbox" id="createinvoice' . $iCnt. '" name="createinvoice' . $iCnt. '" style="font-size:6pt;color:black;"><label for="createinvoice' . $iCnt. '">Generate invoice?</label><br />';   // checked="checked"
						$strSolutionText .= '<br /><input type="checkbox" id="sendemail' . $iCnt. '" name="sendemail' . $iCnt. '" style="font-size:6pt;color:black;"><label for="sendemail' . $iCnt. '">Send Order confirmation e-mail?</label><br />';  
							
						$strSolutionText .= '<br /><center><input type="button" value="Attach Order to Client #'.$iWHMCSClientId.'"  id="addorderOut' . $iCnt. '" onClick="CreateWHMCSOrder(\'addorderOut'.$iCnt. '\',\'clientid=' . $iWHMCSClientId;
						$strSolutionText .= '&domain=' . $strDomainName . '\',\'packageid'.$iCnt. '\',\'createinvoice'.$iCnt. '\',\'sendemail'.$iCnt. '\')"></center></div>';
						
						$bhasWHMCSDomainAccount = TRUE;
		                        }
		                    
		      }      

		      if (!$bFoundinWHCMS && !$bhasWHMCSDomainAccount) echo '<tr style="background-color: #fbb8b8;">';
		      else if (!$bFoundinWHCMS && $bhasWHMCSDomainAccount)  echo '<tr style="background-color: #FBEEEB;">';
		      else if ($iCnt % 2) echo '<tr style="background-color: #dee8f3">';
		      else echo "<tr>";
		      
		      echo '<td style="font-size:8pt;color:black;white-space: nowrap;">' . (++$iCnt + ($iStartNumber-1)). '.</td>';
		      
		      echo '<td style="font-size:8pt;color:#6a6d6e;white-space: nowrap;" align="center">' . $iDomainId . '</td>';
		      
		      if (!$bFoundinWHCMS)   echo '<td style="font-size:8pt;"><strong>' . $strDomainName . '</strong>';
		      else echo '<td style="font-size:8pt"><a href="/controle/admin/clientshosting.php?userid=' .  $iWHMCSClientId  . '&hostingid=' . $iWHMCSHostingId . '">' . $strDomainName . '</a>';
		      echo '</td>';
		         
		      echo '<td style="font-size:8pt;white-space: nowrap"><center>';
		      echo  'Plesk Id # '. $iClientId;
		      echo '</center><br />';
		       echo '<div id="userinfo_output' . $iCnt . '"><center><input type="button" value="Details"  id="userinfobtn' .$iCnt. '" onClick="GetAccountDetailsPlesk(\'userinfo_output'.$iCnt. '\',\'did=' . $iClientId  .  '&ip='.$strIp .'&l=' . $_POST['login_name'] . '&p='.urlencode($_POST['passwd']).'&secure='.$bSecure .'\')"></center></div></td>';

		      if (!$bFoundinWHCMS)        echo '<td style="font-size:8pt;white-space: nowrap;">' . $iWHMCSClientId . '</td>';   
		      else echo '<td style="font-size:8pt;white-space: nowrap;"><a href="/controle/admin/clientsdomains.php?id=' . $iWHMCSClientId . '" style="text-decoration: none;">' . $iWHMCSClientId . '</a></td>';
		  
		      echo '<td style="font-size:8pt;white-space: nowrap">' . (string)$resultNode->data->gen_info->cr_date . '</td>';
		 
		      echo '<td style="font-size:8pt;white-space: nowrap" align="center">';
		     
				switch ($iAccountStatus) {
				   case "0": echo ' <span style="color:grey">active</span>';
					   break;
				   case "2": echo ' <span style="color:red">suspended</span>';
					   break;	
				   case "66": echo ' <span style="color:red">suspended<br/>[<strong>over limits!</strong>]</span>';
					   break;
				   case "256": echo ' <span style="color:red">suspended<br/>[not sure why!]</span>';
					   break;	
				   default: echo ' <span style="color:grey">'.$iAccountStatus.'</span>';
					   break;	
				}

		      echo '</td>';
		      
		     echo '<td style="font-size:8pt;">[<a href="http://www.'. $strDomainName . '" target="_blank">www</a>]</td>';
		       
		      echo '<td style="font-size:8pt;white-space: nowrap;">';
		     // if ((string)$resultNode->data->user->enabled == "false")  echo  '&bull; Enabled: <span style="color:red;font-weight:bold">NO</span> <br />';
		     
		      echo '&bull; Type: ';
		      		switch ($strHtype = (string)$resultNode->data->gen_info->htype) {
				   case "vrt_hst": echo ' <span style="color:black">virtual host</span>';
					   break;
				   case "none": echo ' <span style="color:white;background-color:red;font-weight:bold">NONE</span>';
					   break;		
				   default: echo ' <span style="color:#0398ae">'.$iAccountStatus.'</span>';
					   break;	
				}      
		      echo ' <br />';
		      
		      echo '&bull; DNS: '. (string)$resultNode->data->gen_info->dns_ip_address . '<br />';
		      echo '&bull; Disk Space: ' . bytesToSize1024($resultNode->data->gen_info->real_size) . '<br />';    
		      echo '&bull; Traffic Today: ' . bytesToSize1024($resultNode->data->stat->traffic) . '<br />';
		      echo '&bull; Traffic Yesterday: ' . bytesToSize1024($resultNode->data->stat->traffic_prevday) . '<br />';
		      //echo '&bull; Active Domains: '. (string)$resultNode->data->stat->active_domains . '<br />';
		     // echo '&bull; Sub-Domains: '. (string)$resultNode->data->stat->subdomains . '<br />';      
		     // echo '&bull; Disk Space: ' . bytesToSize1024($resultNode->data->stat->disk_space) . '<br />';     
		      echo '</td>';
		      
		  
		      if (!$bFoundinWHCMS) {
		            
			    echo '<td style="font-size:8pt;color:black;white-space: nowrap">'.$strDomainNotes.'</td>';
			    echo '<td style="font-size:8pt;color:black;">' . $strSolutionText. '</td>';
			    
		      } else if ($row['domainstatus'] == "Suspended" && $iDomainStatus == 0) {
		            
			    echo '<td style="font-size:8pt;color:black;white-space: nowrap">&raquo; Suspended in WHMCS, <strong>Active in Plesk</strong>.</td>';
		            echo '<td><div id="pleskSuspendOut' . $iCnt . '"> <input type="button" value="Plesk: `Suspend`"  id="pleskSuspendBtn' .$iCnt. '" style="color:#5c2de1" onClick="ChangeAccountStatusPlesk(\'pleskSuspendOut'.$iCnt. '\',\'cid=' . $iClientId  . '&did=' . $iDomainId  . '&secure=' . $row['secure'] . '&suspend=1&ip='.$strIp.'&l=' . $_POST['login_name'] .'&p='.urlencode($_POST['passwd']).'\')"></div></td>';         
		      
		      } else if ($row['domainstatus'] == "Active" && $iDomainStatus <> 0) {
		            
			    echo '<td style="font-size:8pt;color:black;white-space: nowrap">&raquo; Active in WHMCS, <strong>Suspended in Plesk</strong>.</td>'; 
		            echo '<td><div id="pleskUnsuspendOut' . $iCnt . '"> <input type="button" value="Plesk: `Unsuspend`"  id="pleskUnsuspendBtn' .$iCnt. '" style="color:#9f1fe1" onClick="ChangeAccountStatusPlesk(\'pleskUnsuspendOut'.$iCnt. '\',\'cid=' . $iClientId  . '&did=' . $iDomainId  . '&secure=' . $row['secure'] . '&suspend=0&ip='.$strIp.'&l=' . $_POST['login_name'] . '&p='.urlencode($_POST['passwd']).'\')"></div></td>';     
		      
		      } else echo '<td></td><td style="font-size:8pt;color:black;">' . $strSolutionText. '</td>';

		      echo "</tr>\n";
		      
		     						      
		     
		 }

  		echo "</table>\n<br /><br />";
  	 
  		if ($iCnt == 0) echo '<div align="center" style="padding-top:50px;padding-bottom:90px;color:red;font-weight:bold">( Sorry, there are no client domains accounts within this range... )</div><br />';
  		else echo '<div style="color:black"><i>(<strong>' . $iCnt . '</strong> of <strong>' .  $iMaxResultsPerPage . '</strong>), client domain accounts found within this range.</i></div><br />';
				
				
			showNavigationButtons($iPageNumber, $_POST['ip'], $_POST['login_name'], $_POST['passwd'], $_POST['secure']);	
			 
		 /*
		 // collect some statistics  
		 echo '&rArr; <span style="color:black">Total Hosting Accounts in Plesk: <strong>' . $iCnt . '</strong></span><br />';
		 //echo '&rArr; <span style="color:black">Total Hosting Accounts in WHMCS: <strong>' .  '</strong></span><br />';
		 if ($iCountMissingFromWHMCS > 0) echo '<br /><br />&rArr; <span style="color:black">Total Hosting Accounts Missing from WHMCS: <span style="font-weight:bold;color:red">'.$iCountMissingFromWHMCS.'</span></span><br />';
		  
				   $result_count = mysql_query("SELECT  COUNT(distinct `tblhosting`.id) AS `count` FROM `tblhosting` WHERE domainstatus = 'Suspended' AND server = 9");              
		                   $row_count = mysql_fetch_assoc($result_count);
				   $iTotalSuspendedAccountsWHMCS = $row_count['count'];
		                   
		 //if ($iTotalSuspendedAccountsWHMCS != $iCountSuspendedInPlesk) echo "FIX THOSE";
		  
		 echo '&rArr; <span style="color:black">Total Hosting Accounts Suspended in Plesk: <strong>' . $iCountSuspendedInPlesk . '</strong></span><br />';
		 echo '&rArr; <span style="color:black">Total Hosting Accounts Suspended in WHMCS: <strong>' . $iTotalSuspendedAccountsWHMCS . '</strong></span><br /><br />';
		 */

			ShowFooter();


	   } //endforeach
     
  } //end if($post) else portion
	
} //end module output function



/***
 * HELPER FUNCTIONS
 */

// --------------------
function bytesToSize1024($bytes = 0, $precision = 2) {
	
	$unit = array('B','KB','MB','GB','TB','PB','EB');
	return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
}
// --------------------
function getViewDomainsButton($ip, $login, $password, $secure, $button_text = 'Browse Accounts', $page = 0) {
	
	        $strHtml  = '<form action="'.$modulelink.'" method="post">';	      
	        $strHtml .= '<input name="login_name" value="'.$login.'" type="hidden"><input name="passwd" value="'.urldecode($password).'" type="hidden">';
	        $strHtml .= '<input name="ip" value="'.$ip.'" type="hidden"><input name="secure" value="'.$secure.'" type="hidden"><input name="page" value="' . $page . '" type="hidden">';
	        $strHtml .= '<input value="' . $button_text . '" type="submit"></form>';
		
		return $strHtml;
}
// --------------------
function showHeaderTitleVersion() {	
	echo '<img src="../modules/addons/plesksync/images/plesksync-icon.png" align="absmiddle"> <strong>Plesk Sync for WHMCS<br /><br />';
}
// --------------------
function showNavigationButtons($page_number, $ip, $login, $password, $secure) {	
			
	echo '<table border="0" align="center" width="40%"><tr>';
	if ($page_number != 0) echo '<td align="center">' . getViewDomainsButton($ip, $login, $password, $secure, '&laquo; Previous Page (' . $page_number . ')', ($page_number - 1) ) . '</td>';
	echo '<td align="center" style="color:black;font-weight:bold">[ Page (' . ($page_number + 1) . ') ]</td>';
	echo '<td align="center">' . getViewDomainsButton($ip, $login, $password, $secure, 'Next Page (' . ($page_number + 2) . ') &raquo;', ($page_number + 1) )  . '</td>';				
	echo '</table><br />';
		
}
// --------------------
function showFooter() {	
	echo '<br /><center><hr width="80%" style="color:grey" /><div style="padding-bottom:25px;color:grey">Plesk Sync</a></div></center>';
}



?>