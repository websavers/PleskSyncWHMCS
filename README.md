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
accounts with your Plesk servers.

Synchronize hosting account statuses between WHMCS & Plesk.  Finds orphaned hosting accounts that are active in Plesk, but not in billing. Attempts to locates the real owner of the hosting account through domain/e-mail.  Imports and creates new clients with invoiced hosting account, including password.

Inconsistencies can start to arise during the nightly WHMCS cron job on the server to analyze due invoices and if necessary, change hosting status on the client server via Plesk API.

Plesk Sync displays all your client servers running the Plesk OS with a compatible API protocol. You can quickly show more detailed statistics such as: uptime, load averages, memory, disk, clients, domains, databases, and mail.

Browse a server to start paging through the client domain accounts existing in Plesk.  Accounts are color-coded, tells a diagnosis and resolution through action buttons and contains statistics on disk space and traffic, with a button for the complete client profile.


Features
--------

   * Ensure synchronicity between WHMCS and Plesk  
   * Interactively Suspend or Unsuspend Plesk client accounts
   * Ajax buttons to carry-out operations rapidly
   * Find orphaned hosting accounts that are active in Plesk  <br /> ';      
   * Synchronize hosting account status (suspended/active)<br />';
   * Auto-locate owner of the hosting account through domain/e-mail<br />';
   * Import, match and create accounts with invoices and e-mails<br />';
   * Open Source, Freeware


Homepage
------------

   * Updated: https://github.com/websavers/PleskSyncWHMCS
   * Original: http://plesksyncwhmcs.sourceforge.net

Screen Shots
------------

   * https://sourceforge.net/project/screenshots.php?group_id=351738

Download
--------

  * Get the latest development copy from GitHub:
        + v2.0 Beta as of (Sep-27, 2018): https://github.com/websavers/PleskSyncWHMCS
   * Get the original/unmaintaned version from SourceForge:
        + v1.0 Beta as of (Sept-12-2010): http://sourceforge.net/projects/plesksyncwhmcs/files/


Requirements
------------

   * WHMCS v7+ (tested with 7.6.1)
   * PHP7.1+ (with cURL and SimpleXML)
   * Plesk v8.1+ for Linux/Unix & Windows [packet protocol > 1.4.1.0] (tested with Plesk 17.8)


Installation
------------

   1) Unzip archive
   2) Upload the entire plesksync folder (including subdirectories) to WHMCS's /modules/addons/ folder
   3) Activate in WHMCS under Setup > Addons then configure on-screen.


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

[Plesk Panel](https://www.plesk.com "Plesk Control Panel") is the leading hosting automation control panel on the market. 
  
Plesk Onyx XML API Protocol Developer Guide:
[https://docs.plesk.com/en-US/onyx/api-rpc/about-xml-api.28709/](https://docs.plesk.com/en-US/onyx/api-rpc/about-xml-api.28709/")

WHMCS API Developer Guide:
[https://developers.whmcs.com/api/](https://developers.whmcs.com/api/)

#### WHMCS API
Utilizes the following functions:
- [AddClient](https://developers.whmcs.com/api-reference/addclient/)
- [AddOrder](https://developers.whmcs.com/api-reference/addorder/)
- [EncryptPassword](https://developers.whmcs.com/api-reference/encryptpassword/)
- [AcceptOrder](https://developers.whmcs.com/api-reference/acceptorder/)

#### PLESK RPC API
Utilizes the following functions:
- client-get (plesksync.class.php)
- server-get_protos (plesksync.class.php)
- domain-get (plesksync.class.php)


Release Log
-----------

   * v2.0b (Sept.27.2018) - complete refactoring of module code to work with WHMCS 7 + Plesk Onyx
   ---
   * v1.0.1b (Sept.23.2010) - added newly created WhmcsApiClass (Whmcs.Api.class.php).  Added auto-detecting of API admin user, to avoid confusion in setup.
   * v1.0.0b (Sept.16.2010) - minor update: added better error exception handling.
   * v1.0.0b (Sept.12.2010) - first public release.


