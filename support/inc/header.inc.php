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

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?php echo (isset($hesk_settings['tmp_title']) ? $hesk_settings['tmp_title'] : $hesk_settings['hesk_title']); ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $hesklang['ENCODING']; ?>" />
	<link href="<?php echo HESK_PATH; ?>hesk_style_v23.css" type="text/css" rel="stylesheet" />
	<link href="<?php echo HESK_PATH; ?>hesk_style.css" type="text/css" rel="stylesheet" />
	<script language="Javascript" type="text/javascript" src="<?php echo HESK_PATH; ?>hesk_javascript.js"></script>
    <?php
	/* Tickets shouldn't be indexed by search engines */
	if (defined('HESK_NO_ROBOTS'))
	{
		?>
		<meta name="robots" content="noindex, nofollow" />
		<?php
	}

	/* If page requires calendar include calendar Javascript and CSS */
	if (defined('CALENDAR'))
	{
		?>
		<script language="Javascript" type="text/javascript" src="<?php echo HESK_PATH; ?>inc/calendar/calendar_js.php"></script>
		<link href="<?php echo HESK_PATH; ?>inc/calendar/calendar.css" type="text/css" rel="stylesheet" />
		<?php
	}

	/* If page requires WYSIWYG editor include TinyMCE Javascript */
	if (defined('WYSIWYG') && $hesk_settings['kb_wysiwyg'])
	{
		?>
        <script type="text/javascript" src="<?php echo HESK_PATH; ?>inc/tiny_mce/tiny_mce.js"></script>
		<?php
	}

	/* If page requires tabs load tabs Javascript and CSS */
	if (defined('LOAD_TABS'))
	{
		?>
		<link href="<?php echo HESK_PATH; ?>inc/tabs/tabber.css" type="text/css" rel="stylesheet" />
		<?php
	}
    ?>

</head>
<body onload="javascript:var i=new Image();i.src='<?php echo HESK_PATH; ?>img/orangebtnover.gif';var i2=new Image();i2.src='<?php echo HESK_PATH; ?>img/greenbtnover.gif';">

<?php
include(HESK_PATH . 'header.txt');
?>

<div align="center">
<table border="0" cellspacing="0" cellpadding="5" class="enclosing">
<tr>
<td>

