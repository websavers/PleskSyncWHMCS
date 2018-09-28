function getServerStats(outputtag, data) {
  
  $('#' + outputtag).html("<img src='" + moduledir + "/images/wait_details.gif'>");		
    
  $.ajax({
    method: 'get',
    url: modulelink + '&ps_action=GetServerStats',
    data: data,
    dataType: 'text',
    success: function (response) {
      $('#' + outputtag).html(response);
    }
  });	
    
}

function ChangeAccountStatusPlesk(outputtag, data) {

  $('#' + outputtag).html("<img src='" + moduledir + "/images/wait_details.gif'>");		        
        
  $.ajax({
    method: 'get',
    url: modulelink + '&ps_action=ChangeStatusInPlesk',
    data: data,
    dataType: 'text',
    success: function (response) {
      $('#' + outputtag).html(response);
    }
  });	
  
}

function GetAccountDetailsPlesk(outputtag, data) {

  $('#' + outputtag).html("<img src='" + moduledir + "/images/wait_details.gif'>");    
  
  $.ajax({
    method: 'get',
    url: modulelink + '&ps_action=GetPleskAccountDetails',
    data: data,
    dataType: 'text',
    success: function (response) {
      $('#' + outputtag).html(response);
    }
  });	
  
}

function ImportPleskAccount(outputtag, data) {
  
  $('#' + outputtag).html("<img src='" + moduledir + "/images/wait_import.gif'>");

  $.ajax({
    method: 'get',
    url: modulelink + '&ps_action=ImportPleskAccount',
    data: data,
    dataType: 'text',
    success: function (response) {
      $('#' + outputtag).html(response);
    }
  });	
  
}

function CreateWHMCSAccount(outputtag, data, first, last, company, email, phone, address1, city, state, postcode, country, login, password, sendemail) {
     
    var strFirst     = $('#' + first).val();
    var strLast      = $('#' + last).val();				
    var strCompany   = $('#' + company).val();
    var strEmail     = $('#' + email).val();
    var strPhone     = $('#' + phone).val();
    var strAddress1  = $('#' + address1).val();
    var strCity      = $('#' + city).val();
    var strState     = $('#' + state).val();
    var strPostcode  = $('#' + postcode).val();
    var strCountry   = $('#' + country).val();
    var strLogin     = $('#' + login).val();
    var strPassword  = $('#' + password).val();
    var bSendemail   = $('#' + sendemail).attr('checked');
         
    $('#' + outputtag).html("<img src='" + moduledir + "/images/wait_import.gif'>");
     
    $.ajax({
      method: 'get',
      url: modulelink + '&ps_action=CreateWHMCSAccount',
      //url: moduledir + '/ajax/ajaxCreateWHMCSAccount.php',
      data: data + "&first=" + strFirst + "&last=" + strLast + "&company=" + strCompany + "&email=" + strEmail + "&phone=" + strPhone + "&address1=" + strAddress1 + "&city=" + strCity + "&state=" + strState + "&postcode=" + strPostcode + "&country=" + strCountry + "&login=" + strLogin + "&password=" + strPassword + "&sendemail=" + bSendemail,
      dataType: 'text',
      success: function (response) {
        $('#' + outputtag).html(response);
      }
    });	
  
}

function CreateWHMCSOrder(outputtag, data, packageid, createinvoice, sendemail) {
     
    var strPackageid    = $('#' + packageid).val();
    var bSendemail      = $('#' + sendemail).attr('checked');
    var bCreateInvoice  = $('#' + createinvoice).attr('checked');

    $('#' + outputtag).html("<img src='" + moduledir + "/images/wait_import.gif'>");
     
    $.ajax({
      method: 'get',
      url: modulelink + '&ps_action=CreateWHMCSOrder',
      //url: moduledir + '/ajax/ajaxCreateWHMCSOrder.php',
      data: data + "&packageid=" + strPackageid + "&sendemail=" + bSendemail + "&createinvoice=" +  bCreateInvoice,
      dataType: 'text',
      success: function (response) {
        $('#' + outputtag).html(response);
      }
    });	
  
}