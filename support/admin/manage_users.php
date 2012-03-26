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
define('HESK_PATH','../');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/database.inc.php');

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
hesk_checkPermission('can_man_users');

/* Possible user features */
$hesk_settings['features'] = array(
'can_view_tickets',		/* User can read tickets */
'can_reply_tickets',	/* User can reply to tickets */
'can_del_tickets',		/* User can delete tickets */
'can_edit_tickets',		/* User can edit tickets */
'can_del_notes',		/* User can delete ticket notes posted by other staff members */
'can_change_cat',		/* User can move ticke to a new category/department */
'can_man_kb',			/* User can manage knowledgebase articles and categories */
'can_man_users',		/* User can create and edit staff accounts */
'can_man_cat',			/* User can manage categories/departments */
'can_man_canned',		/* User can manage canned responses */
'can_man_settings',		/* User can manage help desk settings */
'can_add_archive',		/* User can mark tickets as "Tagged" */
'can_assign_self',		/* User can assign tickets to himself/herself */
'can_assign_others',	/* User can assign tickets to other staff members */
'can_view_unassigned',	/* User can view unassigned tickets */
'can_view_ass_others',	/* User can view tickets that are assigned to other staff */
'can_run_reports',		/* User can run reports and see statistics */
'can_view_online',		/* User can view what staff members are currently online */
);

/* Set default values */
$default_userdata = array(
	'name' => '',
	'email' => '',
	'user' => '',
	'signature' => '',
	'isadmin' => 1,
	'categories' => array('1'),
	'features' => array('can_view_tickets','can_reply_tickets','can_change_cat','can_assign_self','can_view_unassigned','can_view_online'),
	'signature' => '',
	'cleanpass' => '',
);

/* Use any set values, default otherwise */
foreach ($default_userdata as $k => $v)
{
	if (!isset($_SESSION['userdata'][$k]))
    {
    	$_SESSION['userdata'][$k] = $v;
    }
}

$_SESSION['userdata'] = hesk_stripArray($_SESSION['userdata']);

/* What should we do? */
$action = isset($_REQUEST['a']) ? hesk_input($_REQUEST['a']) : '';
if ($action == 'new') {new_user();}
elseif ($action == 'edit') {edit_user();}
elseif ($action == 'save') {update_user();}
elseif ($action == 'remove') {remove();}
elseif ($action == 'autoassign') {autoassign();}
elseif ($action == 'reset_form')
{
	$_SESSION['edit_userdata'] = TRUE;
	header('Location: ./manage_users.php');
}
else {

/* If one came from the Edit page make sure we reset user values */

if (isset($_SESSION['save_userdata']))
{
	$_SESSION['userdata'] = $default_userdata;
    unset($_SESSION['save_userdata']);
}
if (isset($_SESSION['edit_userdata']))
{
	$_SESSION['userdata'] = $default_userdata;
    unset($_SESSION['edit_userdata']);
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
?>

</td>
</tr>
<tr>
<td>

<script language="Javascript" type="text/javascript"><!--
function confirm_delete()
{
if (confirm('<?php echo $hesklang['sure_remove_user']; ?>')) {return true;}
else {return false;}
}
//-->
</script>

<?php
/* This will handle error, success and notice messages */
hesk_handle_messages();
?>

<h3 style="padding-bottom:5px"><?php echo $hesklang['manage_users']; ?> [<a href="javascript:void(0)" onclick="javascript:alert('<?php echo hesk_makeJsString($hesklang['users_intro']); ?>')">?</a>]</h3>

&nbsp;<br />

<div align="center">
<table border="0" width="100%" cellspacing="1" cellpadding="3" class="white">
<tr>
<th class="admin_white" style="text-align:left"><b><i><?php echo $hesklang['name']; ?></i></b></th>
<th class="admin_white" style="text-align:left"><b><i><?php echo $hesklang['email']; ?></i></b></th>
<th class="admin_white" style="text-align:left"><b><i><?php echo $hesklang['username']; ?></i></b></th>
<th class="admin_white" style="text-align:center;white-space:nowrap;width:1px;"><b><i><?php echo $hesklang['administrator']; ?></i></b></th>
<?php
/* Is user rating enabled? */
if ($hesk_settings['rating'])
{
	?>
	<th class="admin_white" style="text-align:center;white-space:nowrap;width:1px;"><b><i><?php echo $hesklang['rating']; ?></i></b></th>
	<?php
}
?>
<th class="admin_white" style="width:100px"><b><i>&nbsp;<?php echo $hesklang['opt']; ?>&nbsp;</i></b></th>
</tr>

<?php
$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` ORDER BY `id` ASC';
$res = hesk_dbQuery($sql);

$i=1;

while ($myuser=hesk_dbFetchAssoc($res))
{

    if (isset($_SESSION['seluser']) && $myuser['id'] == $_SESSION['seluser'])
    {
		$color = 'admin_green';
		unset($_SESSION['seluser']);
	}
    else
    {
		$color = $i ? 'admin_white' : 'admin_gray';
    }

	$tmp   = $i ? 'White' : 'Blue';
    $style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';
	$i	   = $i ? 0 : 1;

    /* User online? */
	if ($hesk_settings['online'])
	{
    	if (isset($hesk_settings['users_online'][$myuser['id']]))
        {
			$myuser['name'] = '<img src="../img/online_on.png" width="16" height="16" alt="'.$hesklang['online'].'" title="'.$hesklang['online'].'" style="vertical-align:text-bottom" /> ' . $myuser['name'];
        }
        else
        {
			$myuser['name'] = '<img src="../img/online_off.png" width="16" height="16" alt="'.$hesklang['offline'].'" title="'.$hesklang['offline'].'" style="vertical-align:text-bottom" /> ' . $myuser['name'];
        }
	}

    if ($myuser['isadmin']) {$myuser['isadmin'] = '<font class="open">'.$hesklang['yes'].'</font>';}
    else {$myuser['isadmin'] = '<font class="resolved">'.$hesklang['no'].'</font>';}

    /* Deleting user with ID 1 (default administrator) is not allowed */
    $edit_code = '<a href="manage_users.php?a=edit&amp;id='.$myuser['id'].'"><img src="../img/edit.png" width="16" height="16" alt="'.$hesklang['edit'].'" title="'.$hesklang['edit'].'" '.$style.' /></a>';
    if ($myuser['id'] == 1)
    {
        $remove_code = ' <img src="../img/blank.gif" width="16" height="16" alt="" style="padding:3px;border:none;" />';
    }
    else
    {
        $remove_code = ' <a href="manage_users.php?a=remove&amp;id='.$myuser['id'].'&amp;token='.hesk_token_echo(0).'" onclick="return confirm_delete();"><img src="../img/delete.png" width="16" height="16" alt="'.$hesklang['remove'].'" title="'.$hesklang['remove'].'" '.$style.' /></a>';
    }

	/* Is auto assign enabled? */
	if ($hesk_settings['autoassign'])
    {
    	if ($myuser['autoassign'])
        {
			$autoassign_code = '<a href="manage_users.php?a=autoassign&amp;s=0&amp;id='.$myuser['id'].'&amp;token='.hesk_token_echo(0).'"><img src="../img/autoassign_on.png" width="16" height="16" alt="'.$hesklang['aaon'].'" title="'.$hesklang['aaon'].'" '.$style.' /></a>';
        }
        else
        {
			$autoassign_code = '<a href="manage_users.php?a=autoassign&amp;s=1&amp;id='.$myuser['id'].'&amp;token='.hesk_token_echo(0).'"><img src="../img/autoassign_off.png" width="16" height="16" alt="'.$hesklang['aaoff'].'" title="'.$hesklang['aaoff'].'" '.$style.' /></a>';
        }
    }
    else
    {
		$autoassign_code = '';
    }

echo <<<EOC
<tr>
<td class="$color">$myuser[name]</td>
<td class="$color"><a href="mailto:$myuser[email]">$myuser[email]</a></td>
<td class="$color">$myuser[user]</td>
<td class="$color">$myuser[isadmin]</td>

EOC;

if ($hesk_settings['rating'])
{
	$alt = $myuser['rating'] ? sprintf($hesklang['rated'], sprintf("%01.1f", $myuser['rating']), ($myuser['ratingneg']+$myuser['ratingpos'])) : $hesklang['not_rated'];
	echo '<td class="'.$color.'" style="text-align:center; white-space:nowrap;"><img src="../img/star_'.(hesk_round_to_half($myuser['rating'])*10).'.png" width="85" height="16" alt="'.$alt.'" title="'.$alt.'" border="0" style="vertical-align:text-bottom" />&nbsp;</td>';
}

echo <<<EOC
<td class="$color" style="text-align:center">$autoassign_code $edit_code $remove_code</td>
</tr>

EOC;
} // End while
?>
</table>
</div>

<br />

<hr />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
			<td class="roundcornerstop"></td>
			<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
		</tr>
		<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>
        <!-- CONTENT -->

<h3 align="center"><?php echo $hesklang['add_user']; ?></h3>

<p align="center"><?php echo $hesklang['req_marked_with']; ?> <font class="important">*</font></p>

<form name="form1" action="manage_users.php" method="post">

<!-- Contact info -->
<table border="0" width="100%">
<tr>
<td width="200" style="text-align:right"><?php echo $hesklang['real_name']; ?>: <font class="important">*</font></td>
<td align="left"><input type="text" name="name" size="40" maxlength="50" value="<?php echo $_SESSION['userdata']['name']; ?>" /></td>
</tr>
<tr>
<td width="200" style="text-align:right"><?php echo $hesklang['email']; ?>: <font class="important">*</font></td>
<td align="left"><input type="text" name="email" size="40" maxlength="255" value="<?php echo $_SESSION['userdata']['email']; ?>" /></td>
</tr>
<tr>
<td width="200" style="text-align:right"><?php echo $hesklang['username']; ?>: <font class="important">*</font></td>
<td><input type="text" name="user" size="40" maxlength="20" value="<?php echo $_SESSION['userdata']['user']; ?>" /></td>
</tr>
<tr>
<td width="200" style="text-align:right"><?php echo $hesklang['pass']; ?>: <font class="important">*</font></td>
<td><input type="password" name="newpass" size="40" maxlength="20" value="<?php echo $_SESSION['userdata']['cleanpass']; ?>" onkeyup="javascript:hesk_checkPassword(this.value)" />
</td>
</tr>
<tr>
<td width="200" style="text-align:right"><?php echo $hesklang['confirm_pass']; ?>: <font class="important">*</font></td>
<td><input type="password" name="newpass2" size="40" maxlength="20" value="<?php echo $_SESSION['userdata']['cleanpass']; ?>" />
</td>
</tr>
<tr>
<td width="200" style="text-align:right"><?php echo $hesklang['pwdst']; ?>:</td>
<td>
<div style="border: 1px solid gray; width: 100px;">
<div id="progressBar"
     style="font-size: 1px; height: 14px; width: 0px; border: 1px solid white;">
</div>
</div>
</td>
</tr>
<tr>
<td valign="top" width="200" style="text-align:right"><?php echo $hesklang['administrator']; ?>: <font class="important">*</font></td>
<td valign="top">
    <label><input type="radio" name="isadmin" value="1" onclick="Javascript:hesk_toggleLayerDisplay('options')" <?php if ($_SESSION['userdata']['isadmin']) echo 'checked="checked"'; ?> /> <?php echo $hesklang['yes'].' '.$hesklang['admin_can']; ?></label><br />
	<label><input type="radio" name="isadmin" value="0" onclick="Javascript:hesk_toggleLayerDisplay('options')" <?php if (!$_SESSION['userdata']['isadmin']) echo 'checked="checked"'; ?> /> <?php echo $hesklang['no'].' '.$hesklang['staff_can']; ?></label>

	<div id="options" style="display: <?php echo ($_SESSION['userdata']['isadmin']) ? 'none' : 'block'; ?>;">
    	<table width="100%" border="0">
		<tr>
		<td valign="top" width="100" style="text-align:right;white-space:nowrap;"><?php echo $hesklang['allowed_cat']; ?>: <font class="important">*</font></td>
		<td valign="top">
		<?php
		$sql_private = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` ORDER BY `cat_order` ASC';
		$result = hesk_dbQuery($sql_private);
		while ($row=hesk_dbFetchAssoc($result))
		{
        	echo '<label><input type="checkbox" name="categories[]" value="' . $row['id'] . '" ';
            if (in_array($row['id'],$_SESSION['userdata']['categories']))
            {
            	echo ' checked="checked" ';
            }
            echo ' />' . $row['name'] . '</label><br /> ';
		}

		?>
        &nbsp;
		</td>
		</tr>
		<tr>
		<td valign="top" width="100" style="text-align:right;white-space:nowrap;"><?php echo $hesklang['allow_feat']; ?>: <font class="important">*</font></td>
		<td valign="top">
        <?php
		foreach ($hesk_settings['features'] as $k)
        {
        	echo '<label><input type="checkbox" name="features[]" value="' . $k . '" ';
            if (in_array($k,$_SESSION['userdata']['features']))
            {
            	echo ' checked="checked" ';
            }
            echo ' />' . $hesklang[$k] . '</label><br /> ';
        }
        ?>
        &nbsp;
		</td>
		</tr>
        </table>
    </div>

</td>
</tr>
<tr>
<td valign="top" width="200" style="text-align:right"><?php echo $hesklang['signature_max']; ?>:</td>
<td><textarea name="signature" rows="6" cols="40"><?php echo $_SESSION['userdata']['signature']; ?></textarea><br />
<?php echo $hesklang['sign_extra']; ?></td>
</tr>
</table>

<!-- Submit -->
<p align="center"><input type="hidden" name="a" value="new" />
<input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
<input type="submit" value="<?php echo $hesklang['create_user']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
|
<a href="manage_users.php?a=reset_form"><?php echo $hesklang['refi']; ?></a></p>

</form>

<script language="Javascript" type="text/javascript"><!--
hesk_checkPassword(document.form1.newpass.value);
//-->
</script>

<p>&nbsp;</p>

		<!-- END CONTENT -->

        </td>
		<td class="roundcornersright">&nbsp;</td>
		</tr>
		<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
		</tr>
	</table>

<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();

} // End else


/*** START FUNCTIONS ***/

function edit_user()
{
	global $hesk_settings, $hesklang, $default_userdata;

	$id = hesk_isNumber($_GET['id'],"$hesklang[int_error]: $hesklang[no_valid_id]");

    $_SESSION['edit_userdata'] = TRUE;

    if (!isset($_SESSION['save_userdata']))
    {
		$sql = 'SELECT `user`,`pass`,`isadmin`,`name`,`email`,`signature`,`categories`,`heskprivileges` AS `features` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` WHERE `id`='.hesk_dbEscape($id).' LIMIT 1';
		$res = hesk_dbQuery($sql);
    	$_SESSION['userdata'] = hesk_dbFetchAssoc($res);

        /* Store original username for display until changes are saved successfully */
        $_SESSION['original_user'] = $_SESSION['userdata']['user'];

        /* A few variables need special attention... */
        if ($_SESSION['userdata']['isadmin'])
        {
	        $_SESSION['userdata']['features'] = $default_userdata['features'];
	        $_SESSION['userdata']['categories'] = $default_userdata['categories'];
        }
        else
        {
	        $_SESSION['userdata']['features'] = explode(',',$_SESSION['userdata']['features']);
	        $_SESSION['userdata']['categories'] = explode(',',$_SESSION['userdata']['categories']);
        }
        $_SESSION['userdata']['cleanpass'] = '';
    }

    /* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>

	</td>
	</tr>
	<tr>
	<td>

	<?php
	/* This will handle error, success and notice messages */
	hesk_handle_messages();
	?>

	<p class="smaller">&nbsp;<a href="manage_users.php" class="smaller"><?php echo $hesklang['manage_users']; ?></a> &gt; <?php echo $hesklang['editing_user'].' '.$_SESSION['original_user']; ?></p>

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
			<td class="roundcornerstop"></td>
			<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
		</tr>
		<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

	<h3 align="center"><?php echo $hesklang['editing_user'].' '.$_SESSION['original_user']; ?></h3>

	<p align="center"><?php echo $hesklang['req_marked_with']; ?> <font class="important">*</font></p>

	<form name="form1" method="post" action="manage_users.php">

	<!-- Contact info -->
	<table border="0" width="100%">
	<tr>
	<td width="200" style="text-align:right"><?php echo $hesklang['real_name']; ?>: <font class="important">*</font></td>
	<td align="left"><input type="text" name="name" size="40" maxlength="50" value="<?php echo $_SESSION['userdata']['name']; ?>" /></td>
	</tr>
	<tr>
	<td width="200" style="text-align:right"><?php echo $hesklang['email']; ?>: <font class="important">*</font></td>
	<td align="left"><input type="text" name="email" size="40" maxlength="255" value="<?php echo $_SESSION['userdata']['email']; ?>" /></td>
	</tr>
	<tr>
	<td width="200" style="text-align:right"><?php echo $hesklang['username']; ?>: <font class="important">*</font></td>
	<td><input type="text" name="user" size="40" maxlength="20" value="<?php echo $_SESSION['userdata']['user']; ?>" /></td>
	</tr>
	<tr>
	<td width="200" style="text-align:right"><?php echo $hesklang['pass']; ?>:</td>
	<td><input type="password" name="newpass" size="40" maxlength="20" value="<?php echo $_SESSION['userdata']['cleanpass']; ?>" onkeyup="javascript:hesk_checkPassword(this.value)" /></td>
	</tr>
	<tr>
	<td width="200" style="text-align:right"><?php echo $hesklang['confirm_pass']; ?>:</td>
	<td><input type="password" name="newpass2" size="40" maxlength="20" value="<?php echo $_SESSION['userdata']['cleanpass']; ?>" /></td>
	</tr>
	<tr>
	<td width="200" style="text-align:right"><?php echo $hesklang['pwdst']; ?>:</td>
	<td>
	<div style="border: 1px solid gray; width: 100px;">
	<div id="progressBar"
	     style="font-size: 1px; height: 14px; width: 0px; border: 1px solid white;">
	</div>
	</div>
	</td>
	</tr>
	<tr>
	<td valign="top" width="200" style="text-align:right"><?php echo $hesklang['administrator']; ?>: <font class="important">*</font></td>
	<td valign="top">
	    <label><input type="radio" name="isadmin" value="1" onclick="Javascript:hesk_toggleLayerDisplay('options')" <?php if ($_SESSION['userdata']['isadmin']) echo 'checked="checked"'; ?> /> <?php echo $hesklang['yes'].' '.$hesklang['admin_can']; ?></label><br />
		<label><input type="radio" name="isadmin" value="0" onclick="Javascript:hesk_toggleLayerDisplay('options')" <?php if (!$_SESSION['userdata']['isadmin']) echo 'checked="checked"'; ?> /> <?php echo $hesklang['no'].' '.$hesklang['staff_can']; ?></label>

		<div id="options" style="display: <?php echo ($_SESSION['userdata']['isadmin']) ? 'none' : 'block'; ?>;">
	    	<table width="100%" border="0">
			<tr>
			<td valign="top" width="100" style="text-align:right;white-space:nowrap;"><?php echo $hesklang['allowed_cat']; ?>: <font class="important">*</font></td>
			<td valign="top">
			<?php
			$sql_private = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` ORDER BY `cat_order` ASC';
			$result = hesk_dbQuery($sql_private);
			while ($row=hesk_dbFetchAssoc($result))
			{
	        	echo '<label><input type="checkbox" name="categories[]" value="' . $row['id'] . '" ';
	            if (in_array($row['id'],$_SESSION['userdata']['categories']))
	            {
	            	echo ' checked="checked" ';
	            }
	            echo ' />' . $row['name'] . '</label><br /> ';
			}

			?>
	        &nbsp;
			</td>
			</tr>
			<tr>
			<td valign="top" width="100" style="text-align:right;white-space:nowrap;"><?php echo $hesklang['allow_feat']; ?>: <font class="important">*</font></td>
			<td valign="top">
	        <?php
			foreach ($hesk_settings['features'] as $k)
	        {
	        	echo '<label><input type="checkbox" name="features[]" value="' . $k . '" ';
	            if (in_array($k,$_SESSION['userdata']['features']))
	            {
	            	echo ' checked="checked" ';
	            }
	            echo ' />' . $hesklang[$k] . '</label><br /> ';
	        }
	        ?>
	        &nbsp;
			</td>
			</tr>
	        </table>
	    </div>

	</td>
	</tr>
	<tr>
	<td valign="top" width="200" style="text-align:right"><?php echo $hesklang['signature_max']; ?>:</td>
	<td><textarea name="signature" rows="6" cols="40"><?php echo $_SESSION['userdata']['signature']; ?></textarea><br />
	<?php echo $hesklang['sign_extra']; ?></td>
	</tr>
	</table>

	<!-- Submit -->
	<p align="center"><input type="hidden" name="a" value="save" />
	<input type="hidden" name="userid" value="<?php echo $id; ?>" />
    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
	<input type="submit" value="<?php echo $hesklang['save_changes']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
    |
    <a href="manage_users.php"><?php echo $hesklang['dich']; ?></a></p>

	</form>

	<script language="Javascript" type="text/javascript"><!--
	hesk_checkPassword(document.form1.newpass.value);
	//-->
	</script>

        </td>
		<td class="roundcornersright">&nbsp;</td>
		</tr>
		<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
		</tr>
	</table>

	<?php
	require_once(HESK_PATH . 'inc/footer.inc.php');
	exit();
} // End edit_user()


function new_user() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_POST['token']);

	$myuser = hesk_validateUserInfo();

	/* Can view unassigned tickets? */
	if (in_array('can_view_unassigned',$myuser['features']))
	{
		$sql_where = '';
		$sql_what = '';
	}
	else
	{
		$sql_where = ' , `notify_new_unassigned`, `notify_reply_unassigned` ';
		$sql_what = " , '0', '0' ";
	}

    /* Categories and Features will be stored as a string */
    $myuser['categories'] = implode(',',$myuser['categories']);
    $myuser['features'] = implode(',',$myuser['features']);

    /* Check for duplicate usernames */
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` WHERE `user` = \''.hesk_dbEscape($myuser['user']).'\' LIMIT 1';
	$result = hesk_dbQuery($sql);
	if (hesk_dbNumRows($result) != 0)
	{
        hesk_process_messages($hesklang['duplicate_user'],'manage_users.php');
	}

    /* Admins will have access to all features and categories */
    if ($myuser['isadmin'])
    {
		$myuser['categories'] = '';
		$myuser['features'] = '';
    }

	$sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."users` (`user`,`pass`,`isadmin`,`name`,`email`,`signature`,`categories`,`heskprivileges` $sql_where) VALUES (
	'".hesk_dbEscape($myuser['user'])."',
	'".hesk_dbEscape($myuser['pass'])."',
	'".hesk_dbEscape($myuser['isadmin'])."',
	'".hesk_dbEscape($myuser['name'])."',
	'".hesk_dbEscape($myuser['email'])."',
	'".hesk_dbEscape($myuser['signature'])."',
	'".hesk_dbEscape($myuser['categories'])."',
	'".hesk_dbEscape($myuser['features'])."'
	$sql_what )";

	$result = hesk_dbQuery($sql);

    $_SESSION['seluser'] = hesk_dbInsertID();

    unset($_SESSION['userdata']);

    hesk_process_messages(sprintf($hesklang['user_added_success'],$myuser['user'],$myuser['cleanpass']),'./manage_users.php','SUCCESS');
} // End new_user()


function update_user() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_POST['token']);

    $_SESSION['save_userdata'] = TRUE;

	$tmp = hesk_isNumber($_POST['userid'],"$hesklang[int_error]: $hesklang[no_valid_id]");
    $_SERVER['PHP_SELF'] = './manage_users.php?a=edit&id='.$tmp;

	$myuser = hesk_validateUserInfo(0,$_SERVER['PHP_SELF']);
    $myuser['id'] = $tmp;

	/* If can't view assigned changes this */
	if (in_array('can_view_unassigned',$myuser['features']))
	{
		$sql_where = "";
	}
	else
	{
		$sql_where = " , `notify_new_unassigned`='0', `notify_reply_unassigned`='0' ";
	}

    /* Check for duplicate usernames */
	$sql = 'SELECT `id` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` WHERE `user` = \''.hesk_dbEscape($myuser['user']).'\' LIMIT 1';
	$result = hesk_dbQuery($sql);
	if (hesk_dbNumRows($result) == 1)
	{
    	$tmp = hesk_dbFetchAssoc($result);
        if ($tmp['id'] != $myuser['id'])
        {
        	hesk_process_messages($hesklang['duplicate_user'],$_SERVER['PHP_SELF']);
        }
	}

    /* Admins will have access to all features and categories */
    if ($myuser['isadmin'])
    {
		$myuser['categories'] = '';
		$myuser['features'] = '';
    }
	/* Not admin */
	else
    {
		/* Categories and Features will be stored as a string */
	    $myuser['categories'] = implode(',',$myuser['categories']);
	    $myuser['features'] = implode(',',$myuser['features']);

    	/* Unassign tickets from categories that the user had access before but doesn't anymore */
		$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `owner`=0 WHERE `owner`=".hesk_dbEscape($myuser['id'])." AND `category` NOT IN (".$myuser['categories'].")";
        $res = hesk_dbQuery($sql);
    }

	$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET
    `user`='".hesk_dbEscape($myuser['user'])."',
    `name`='".hesk_dbEscape($myuser['name'])."',
    `email`='".hesk_dbEscape($myuser['email'])."',
    `signature`='".hesk_dbEscape($myuser['signature'])."',";
	if (isset($myuser['pass']))
	{
	    $sql .= "`pass`='".hesk_dbEscape($myuser['pass'])."',";
	}
	$sql .= "
    `categories`='".hesk_dbEscape($myuser['categories'])."',
    `isadmin`='".hesk_dbEscape($myuser['isadmin'])."',
    `heskprivileges`='".hesk_dbEscape($myuser['features'])."'
    $sql_where
    WHERE `id`=".hesk_dbEscape($myuser['id'])." LIMIT 1";
	$result = hesk_dbQuery($sql);

    unset($_SESSION['save_userdata']);
    unset($_SESSION['userdata']);

    hesk_process_messages( $hesklang['user_profile_updated_success'],$_SERVER['PHP_SELF'],'SUCCESS');
} // End update_profile()


function hesk_validateUserInfo($pass_required = 1, $redirect_to = './manage_users.php') {
	global $hesk_settings, $hesklang;

    $hesk_error_buffer = '';

	$myuser['name']		 = hesk_input($_POST['name']) or $hesk_error_buffer .= '<li>' . $hesklang['enter_real_name'] . '</li>';
	$myuser['email']	 = hesk_validateEmail($_POST['email'],'ERR',0) or $hesk_error_buffer .= '<li>' . $hesklang['enter_valid_email'] . '</li>';
	$myuser['user']		 = hesk_input($_POST['user']) or $hesk_error_buffer .= '<li>' . $hesklang['enter_username'] . '</li>';
	$myuser['signature'] = hesk_input($_POST['signature']);
	$myuser['isadmin']	 = intval($_POST['isadmin']) ? 1 : 0;

    /* If it's not admin at least one category and fature is required */
    $myuser['categories']	= array();
    $myuser['features']		= array();

    if ($myuser['isadmin']==0)
    {
    	if (empty($_POST['categories']))
        {
			$hesk_error_buffer .= '<li>' . $hesklang['asign_one_cat'] . '</li>';
        }
        else
        {
			foreach ($_POST['categories'] as $tmp)
			{
				if ($tmp = intval($tmp))
				{
					$myuser['categories'][] = $tmp;
				}
			}
        }

    	if (empty($_POST['features']))
        {
			$hesk_error_buffer .= '<li>' . $hesklang['asign_one_feat'] . '</li>';
        }
        else
        {
			foreach ($_POST['features'] as $tmp)
			{
				if (in_array($tmp,$hesk_settings['features']))
				{
					$myuser['features'][] = $tmp;
				}
			}
        }
	}

	if (strlen($myuser['signature'])>255)
    {
    	$hesk_error_buffer .= '<li>' . $hesklang['signature_long'] . '</li>';
    }

    /* Password */
	$myuser['cleanpass'] = '';

	$newpass = hesk_input($_POST['newpass']);
	$passlen = strlen($newpass);

	if ($pass_required || $passlen > 0)
	{
        /* At least 5 chars? */
        if ($passlen < 5)
        {
        	$hesk_error_buffer .= '<li>' . $hesklang['password_not_valid'] . '</li>';
        }
        /* Check password confirmation */
        else
        {
        	$newpass2 = hesk_input($_POST['newpass2']);

			if ($newpass != $newpass2)
			{
				$hesk_error_buffer .= '<li>' . $hesklang['passwords_not_same'] . '</li>';
			}
            else
            {
                $myuser['pass'] = hesk_Pass2Hash($newpass);
                $myuser['cleanpass'] = $newpass;
            }
        }
	}

    /* Save entered info in session so we don't loose it in case of errors */
	$_SESSION['userdata'] = $myuser;

    /* Any errors */
    if (strlen($hesk_error_buffer))
    {
    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,$redirect_to);
    }

	return $myuser;

} // End hesk_validateUserInfo()


function remove() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_GET['token']);

	$myuser = hesk_isNumber($_GET['id'],$hesklang['no_valid_id']);

    /* You can't delete the default user */
	if ($myuser == 1)
    {
        hesk_process_messages($hesklang['cant_del_admin'],'./manage_users.php');
    }

    /* You can't delete your own account (the one you are logged in) */
	if ($myuser == $_SESSION['id'])
    {
        hesk_process_messages($hesklang['cant_del_own'],'./manage_users.php');
    }

    /* Un-assign all tickets for this user */
    $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` SET `owner`=0 WHERE `owner`='.hesk_dbEscape($myuser).' ';
    $res = hesk_dbQuery($sql);

    /* Delete user info */
	$sql = 'DELETE FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` WHERE `id`='.hesk_dbEscape($myuser).' LIMIT 1';
	$res = hesk_dbQuery($sql);
	if (hesk_dbAffectedRows() != 1)
    {
        hesk_process_messages($hesklang['int_error'].': '.$hesklang['user_not_found'],'./manage_users.php');
    }

    hesk_process_messages($hesklang['sel_user_removed'],'./manage_users.php','SUCCESS');
} // End remove()


function autoassign() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_GET['token']);

	$myuser = hesk_isNumber($_GET['id'],$hesklang['no_valid_id']);
    $_SESSION['seluser'] = $myuser;

    if (intval($_GET['s']))
    {
		$autoassign = 1;
        $tmp = $hesklang['uaaon'];
    }
    else
    {
        $autoassign = 0;
        $tmp = $hesklang['uaaoff'];
    }

	/* Update auto-assign settings */
	$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` SET `autoassign`=\''.hesk_dbEscape($autoassign).'\' WHERE `id`='.hesk_dbEscape($myuser).' LIMIT 1';
	$res = hesk_dbQuery($sql);
	if (hesk_dbAffectedRows() != 1)
    {
        hesk_process_messages($hesklang['int_error'].': '.$hesklang['user_not_found'],'./manage_users.php');
    }

    hesk_process_messages($tmp,'./manage_users.php','SUCCESS');
} // End autoassign()
?>
