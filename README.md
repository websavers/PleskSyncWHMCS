Plesk Sync for WHMCS
====================

Modified / Updated by:

Websavers Inc.
https://websavers.ca

Originally Written by:

Shawn Reimerdes
shawnreimerdes@users.sourceforge.net
WebNames.com.br
http://webnames.com.br


About
-----

Plesk Sync is an add-on module for WHMCS to import, control, create and synchronize client hosting
accounts with your Parallel Plesk servers.

Synchronize hosting account statuses between WHMCS & Plesk.  Finds orphaned hosting accounts that are active in Plesk, but not in billing. Attempts to locates the real owner of the hosting account through domain/e-mail.  Imports and creates new clients with invoiced hosting account, including password.

Inconsistencies can start to arise during the nightly WHMCS cron job on the server to analyze due invoices and if necessary, change hosting status on the client server via Plesk API.

Plesk Sync displays all your client servers running the Plesk OS with a compatible API protocol. You can quickly show more detailed statistics such as: uptime, load averages, memory, disk, clients, domains, databases, and mail.

Browse a server to start paging through the client domain accounts existing in Plesk.  Accounts are color-coded, tells a diagnosis and resolution through action buttons and contains statistics on disk space and traffic, with a button for the complete client profile.


Features
--------

   * Ensure synchronicity between WHMCS and Plesk  
   * Interactively Suspend or Unsuspend Plesk client accounts
   * Ajax buttons to carry-out operations rapidly
   * Open Source, Freeware


Homepage
------------

   * http://plesksyncwhmcs.sourceforge.net

Screen Shots
------------

   * https://sourceforge.net/project/screenshots.php?group_id=351738



Download
--------

   * Get the latest version from SourceForge:
   
         + v1.0 Beta as of (Sept-12-2010): http://sourceforge.net/projects/plesksyncwhmcs/files/




Requirements
------------

   * WHMCS v4+
   * PHP5 (with cURL and SimpleXML)
   * Plesk v8.1+ for Linux/Unix & Windows (packet protocol > 1.4.1.0)


Installation
------------

   1) Unzip archive.
   
   2) Edit config.php.  You will need to edit the following entries:
   
         $whmcs_api_url = 'http://yoursite.com/whmcs_default_directory/includes/api.php';
         
         $whmcs_addorder_payment = "paypal";                # payment gateway for new invoiced hosting 
         $whmcs_addorder_billingcycle = "Monthly";          # invoice billing cycle for new invoice hosting
             
   3) Upload via FTP the folder "plesksync" (including subdirectories) to your whmcs web server,
      at this location: **/whmcs_default_directory/modules/admin/**


Usage Instructions
------------------

   1) Launch program from within WHMCS admin control panel: Utilities -> Addon Modules -> Plesksync
   
   2) You will then see a list of all your Plesk servers, each one will be contacted to determine the protocol supported.  
   
   3) Click the Browse Accounts button to begin viewing client domain accounts. You will see the first page of results, a list of accounts with detailed information about each.

   4) If there are any issues with an account there will be a diagnosis message and also buttons to make a quick resolution.
 
   5) Resolution options include:
      * Suspend in Plesk 
      * Unsuspend in Plesk
      * Verify & Import

   6) Follow the instructions given during the resolution process.


Source Code
---------
All source code is included.  This software is distributed as is, please feel free to modify it as you like.

Please submit any bugs or code corrections!


Reference
---------

[WHMCS](http://www.whmcs.com "WHMCS") is an all-in-one client management, billing & support solution for online businesses.

[Parallels Plesk Panel](http://www.parallels.com/products/plesk/ "Parallel Plesk Panel") is the leading hosting automation control panel on the market. 
  
Parallels Plesk Panel 9.2: API RPC Protocol Developer Guide:
[http://download1.parallels.com/Plesk/PPP9/Doc/en-US/plesk-9.2-api-rpc/](http://download1.parallels.com/Plesk/PPP9/Doc/en-US/plesk-9.2-api-rpc/ "PLESK 9.2 RPC API")
[http://download1.parallels.com/Plesk/PPP9/Doc/en-US/plesk-9.2-api-rpc-guide/33181.htm](http://download1.parallels.com/Plesk/PPP9/Doc/en-US/plesk-9.2-api-rpc-guide/33181.htm "XML PLESK API")

#### WHMCS API
Utilizes the following functions:
- [AddClient](http://wiki.whmcs.com/API:Add_Client "WHMCS API - Add Client") (ajaxCreateWHMCSAccount.php)
- [AddOrder](http://wiki.whmcs.com/API:Add_Order "WHMCS API - Add Order") (ajaxCreateWHMCSOrder.php)
- [EncryptPassword](http://wiki.whmcs.com/API:Encrypt_Password "WHMCS API - Encrypt Password") (ajaxCreateWHMCSOrder.php)
- [AcceptOrder](http://wiki.whmcs.com/API:Accept_Order "WHMCS API - Accept Order") (ajaxCreateWHMCSOrder.php)

#### PLESK RPC API
Utilizes the following functions:
- client-get (ajaxGetPleskAccountDetails.php, ajaxImportPleskAccount.php)
- server-get_protos (plesksync.class.php)
- domain-get (plesksync.class.php)



Release Log
-----------

   * v1.0.1 Beta (Sept.23.2010) - added newly created WhmcsApiClass (Whmcs.Api.class.php).  Added auto-detecting of API admin user, to avoid confusion in setup.
   * v1.0 Beta (Sept.16.2010) - minor update: added better error exception handling.
   * v1.0 Beta (Sept.12.2010) - first public release.









