<?php
/*******************************************************************************
*  Title: Help Desk Software HESK
*  Version: 2.3 from 15th September 2011
*  Author: Klemen Stirn
*  Website: http://www.hesk.com
********************************************************************************
*  COPYRIGHT AND TRADEMARK NOTICE
*  Copyright 2005-2011 Klemen Stirn. All Rights Reserved.
*  HESK is a registered trademark of Klemen Stirn.

*  The HESK may be used and modified free of charge by anyone
*  AS LONG AS COPYRIGHT NOTICES AND ALL THE COMMENTS REMAIN INTACT.
*  By using this code you agree to indemnify Klemen Stirn from any
*  liability that might arise from it's use.

*  Selling the code for this program, in part or full, without prior
*  written consent is expressly forbidden.

*  Using this code, in part or full, to create derivate work,
*  new scripts or products is expressly forbidden. Obtain permission
*  before redistributing this software over the Internet or in
*  any other medium. In all cases copyright and header must remain intact.
*  This Copyright is in full effect in any country that has International
*  Trade Agreements with the United States of America or
*  with the European Union.

*  Removing any of the copyright notices without purchasing a license
*  is expressly forbidden. To remove HESK copyright notice you must purchase
*  a license for this script. For more information on how to obtain
*  a license please visit the page below:
*  https://www.hesk.com/buy.php
*******************************************************************************/

define('IN_SCRIPT',1);
define('HESK_PATH','./');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/database.inc.php');

hesk_session_start();

/* A security check */
hesk_token_check($_GET['token']);

$trackingID = strtoupper(hesk_input($_GET['track'],"$hesklang[int_error]: $hesklang[no_trackID]."));
$status = hesk_isNumber($_GET['s'],"$hesklang[int_error]: $hesklang[status_not_valid].");

$locked = 0;

if ($status==3) // Closed
{
	$action = $hesklang['closed'];
    $revision = sprintf($hesklang['thist3'],hesk_date(),$hesklang['customer']);

    if ($hesk_settings['custopen'] != 1)
    {
    	$locked = 1;
    }
}
else // Opened
{
	$status = 1;

	/* Is customer reopening tickets enabled? */
	if (!$hesk_settings['custopen'])
	{
		hesk_error($hesklang['attempt']);
	}

	$action = $hesklang['opened'];
    $revision = sprintf($hesklang['thist4'],hesk_date(),$hesklang['customer']);
}

/* Connect to database */
hesk_dbConnect();

$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET
	`status`='".hesk_dbEscape($status)."',
    `locked`='".hesk_dbEscape($locked)."',
    `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."')
    WHERE `trackid`='".hesk_dbEscape($trackingID)."'
    AND `locked` != '1' LIMIT 1";
$result = hesk_dbQuery($sql);

if (hesk_dbAffectedRows() != 1)
{
	hesk_error($hesklang['elocked']);
}

hesk_process_messages($hesklang['your_ticket_been'].' '.$action,'ticket.php?track='.$trackingID.'&Refresh='.rand(10000,99999),'SUCCESS');
?>
