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
hesk_checkPermission('can_view_tickets');
$can_del_notes		 = hesk_checkPermission('can_del_notes',0);
$can_reply			 = hesk_checkPermission('can_reply_tickets',0);
$can_delete			 = hesk_checkPermission('can_del_tickets',0);
$can_edit			 = hesk_checkPermission('can_edit_tickets',0);
$can_archive		 = hesk_checkPermission('can_add_archive',0);
$can_assign_self	 = hesk_checkPermission('can_assign_self',0);
$can_view_unassigned = hesk_checkPermission('can_view_unassigned',0);

$trackingID = isset($_REQUEST['track']) ? hesk_cleanID($_REQUEST['track']) : '';
if (empty($trackingID))
{
	print_form();
}

$_SERVER['PHP_SELF'] = 'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999);

/* Get ticket info */
$sql = "SELECT `t1`.* , `t2`.name AS `repliername`
FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` AS `t1` LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t2` ON `t1`.`replierid` = `t2`.`id`
WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1";
$result = hesk_dbQuery($sql);
if (hesk_dbNumRows($result) != 1)
{
    hesk_process_messages($hesklang['ticket_not_found'],'NOREDIRECT');
    print_form();
}
$ticket = hesk_dbFetchAssoc($result);

/* Permission to view this ticket? */
if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && ! hesk_checkPermission('can_view_ass_others',0))
{
	hesk_error($hesklang['ycvtao']);
}

if (!$ticket['owner'] && ! $can_view_unassigned)
{
	hesk_error($hesklang['ycovtay']);
}

/* Set last replier name */
if ($ticket['lastreplier'])
{
	if (empty($ticket['repliername']))
	{
		$ticket['repliername'] = $hesklang['staff'];
	}
}
else
{
	$ticket['repliername'] = $ticket['name'];
}

/* Get category name and ID */
$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`=".hesk_dbEscape($ticket['category'])." LIMIT 1";
$result = hesk_dbQuery($sql);
/* If this category has been deleted use the default category with ID 1 */
if (hesk_dbNumRows($result) != 1)
{
	$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`=1 LIMIT 1";
	$result = hesk_dbQuery($sql);
}
$category = hesk_dbFetchAssoc($result);

/* Is this user allowed to view tickets inside this category? */
hesk_okCategory($category['id']);

/* Delete post action */
if (isset($_GET['delete_post']) && $can_delete && hesk_token_check($_GET['token']))
{
	$n = hesk_isNumber($_GET['delete_post']);
    if ($n)
    {
		/* Get last reply ID, we'll need it later */
		$sql = "SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`=".hesk_dbEscape($ticket['id'])." ORDER BY `id` DESC LIMIT 1";
		$res = hesk_dbQuery($sql);
        $last_reply_id = hesk_dbResult($res,0,0);

		/* Delete this reply */
		$sql = "DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `id`=".hesk_dbEscape($n)." AND `replyto`=".hesk_dbEscape($ticket['id'])." LIMIT 1";
		$res = hesk_dbQuery($sql);

        /* Reply wasn't deleted */
        if (hesk_dbAffectedRows() != 1)
        {
			hesk_process_messages($hesklang['repl1'],$_SERVER['PHP_SELF']);
        }
        else
        {
			/* Reply deleted. Need to update status and last replier? */
			$sql = "SELECT `staffid` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".hesk_dbEscape($ticket['id'])."' ORDER BY `id` DESC LIMIT 1";
			$res = hesk_dbQuery($sql);
			if (hesk_dbNumRows($res))
			{
				$replier_id = hesk_dbResult($res,0,0);
                $last_replier = $replier_id ? 1 : 0;

				/* Change status? */
                $status_sql = '';
				if ($last_reply_id == $n)
				{
					$status = $ticket['locked'] ? 3 : ($last_replier ? 2 : 1);
                    $status_sql = " , `status`='".hesk_dbEscape($status)."' ";
				}

				$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `lastreplier`='".hesk_dbEscape($last_replier)."', `replierid`=".hesk_dbEscape($replier_id)." $status_sql WHERE `id`=".hesk_dbEscape($ticket['id'])." LIMIT 1";
				$res = hesk_dbQuery($sql);
			}
			else
			{
            	$status = $ticket['locked'] ? 3 : 0;
				$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `lastreplier`='0', `status`='".hesk_dbEscape($status)."' WHERE `id`=".hesk_dbEscape($ticket['id'])." LIMIT 1";
				$res = hesk_dbQuery($sql);
			}

			hesk_process_messages($hesklang['repl'],$_SERVER['PHP_SELF'],'SUCCESS');
        }
    }
    else
    {
    	hesk_process_messages($hesklang['repl0'],$_SERVER['PHP_SELF']);
    }
}

/* Delete notes action */
if (isset($_GET['delnote']) && hesk_token_check($_GET['token']))
{
	$n = hesk_isNumber($_GET['delnote']);
    if ($n)
    {
    	if ($can_del_notes)
        {
			$sql = "DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` WHERE `id`=".hesk_dbEscape($n)." LIMIT 1";
        }
        else
        {
        	$sql = "DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` WHERE `id`=".hesk_dbEscape($n)." AND `who`=".hesk_dbEscape($_SESSION['id'])." LIMIT 1";
        }
		$res = hesk_dbQuery($sql);
    }
    header('Location: admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
    exit();
}

/* Add a note action */
if (isset($_POST['notemsg']) && hesk_token_check($_POST['token']))
{
	$msg = hesk_input($_POST['notemsg']);
    if ($msg)
    {
    	$msg = nl2br(hesk_makeURL($msg));
		$sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` (`ticket`,`who`,`dt`,`message`) VALUES (
        '".hesk_dbEscape($ticket['id'])."',
        '".hesk_dbEscape($_SESSION['id'])."',
        NOW(),
        '".hesk_dbEscape($msg)."')";
		hesk_dbQuery($sql);
    }
    header('Location: admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
    exit();
}

/* Delete attachment action */
if (isset($_GET['delatt']) && hesk_token_check($_GET['token']))
{
	if ( ! $can_delete || ! $can_edit)
    {
		hesk_process_messages($hesklang['no_permission'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
    }

	$att_id = hesk_isNumber($_GET['delatt'],$hesklang['inv_att_id']);

	$reply = isset($_GET['reply']) ? intval($_GET['reply']) : 0;
	if ($reply < 1)
	{
		$reply = 0;
	}

	/* Get attachment info */
	$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id`=".hesk_dbEscape($att_id)." LIMIT 1";
	$res = hesk_dbQuery($sql);
	if (hesk_dbNumRows($res) != 1)
	{
		hesk_process_messages($hesklang['id_not_valid'].' (att_id)','admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
	}
	$att = hesk_dbFetchAssoc($res);

	/* Is ticket ID valid for this attachment? */
	if ($att['ticket_id'] != $trackingID)
	{
		hesk_process_messages($hesklang['trackID_not_found'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
	}

	/* Delete file from server */
	unlink('../attachments/'.$att['saved_name']);

	/* Delete attachment from database */
	$sql = 'DELETE FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'attachments` WHERE `att_id`='.hesk_dbEscape($att_id);
	hesk_dbQuery($sql);

	/* Update ticket or reply in the database */
    $revision = sprintf($hesklang['thist12'],hesk_date(),$att['real_name'],$_SESSION['name'].' ('.$_SESSION['user'].')');
	if ($reply)
	{
		$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'replies` SET `attachments`=REPLACE(`attachments`,\''.hesk_dbEscape($att_id.'#'.$att['real_name']).','.'\',\'\') WHERE `id`='.hesk_dbEscape($reply).' LIMIT 1';
		hesk_dbQuery($sql);
        $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` SET `history`=CONCAT(`history`,\''.hesk_dbEscape($revision).'\') WHERE `id`='.hesk_dbEscape($ticket['id']).' LIMIT 1';
		hesk_dbQuery($sql);
	}
	else
	{
		$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` SET `attachments`=REPLACE(`attachments`,\''.hesk_dbEscape($att_id.'#'.$att['real_name']).','.'\',\'\'), `history`=CONCAT(`history`,\''.hesk_dbEscape($revision).'\') WHERE `id`='.hesk_dbEscape($ticket['id']).' LIMIT 1';
		hesk_dbQuery($sql);
	}

	hesk_process_messages($hesklang['kb_att_rem'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'SUCCESS');
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* List of categories */
$sql = "SELECT `id`,`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` ASC";
$result = hesk_dbQuery($sql);
$categories_options='';
while ($row=hesk_dbFetchAssoc($result))
{
    if ($row['id'] == $ticket['category']) {continue;}
    $categories_options.='<option value="'.$row['id'].'">'.$row['name'].'</option>';
}

/* List of users */
$admins = array();
$sql = "SELECT `id`,`name`,`isadmin`,`categories`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ORDER BY `id` ASC";
$result = hesk_dbQuery($sql);
while ($row=hesk_dbFetchAssoc($result))
{
	/* Is this an administrator? */
	if ($row['isadmin'])
    {
	    $admins[$row['id']]=$row['name'];
	    continue;
    }

	/* Not admin, is user allowed to view tickets? */
	if (strpos($row['heskprivileges'], 'can_view_tickets') !== false)
	{
		/* Is user allowed to access this category? */
		$cat=substr($row['categories'], 0);
		$row['categories']=explode(',',$cat);
		if (in_array($ticket['category'],$row['categories']))
		{
			$admins[$row['id']]=$row['name'];
			continue;
		}
	}
}

/* Get replies */
$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".hesk_dbEscape($ticket['id'])."' ORDER BY `id` ASC";
$result = hesk_dbQuery($sql);
$replies = hesk_dbNumRows($result);

/* Print admin navigation */
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

<h3 style="padding-bottom:5px"> &nbsp;

<?php
if ($ticket['archive'])
{
	echo '<img src="../img/tag.png" width="16" height="16" alt="'.$hesklang['archived'].'" title="'.$hesklang['archived'].'"  border="0" style="vertical-align:text-bottom" /> ';
}
if ($ticket['locked'])
{
    echo '<img src="../img/lock.png" width="16" height="16" alt="'.$hesklang['loc'].' - '.$hesklang['isloc'].'" title="'.$hesklang['loc'].' - '.$hesklang['isloc'].'" border="0" style="vertical-align:text-bottom" /> ';
}
echo $ticket['subject'];
?></h3>


<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>
    <!-- START TICKET HEAD -->

	<table border="0" cellspacing="1" cellpadding="1" width="100%">
	<?php

	$tmp = '';
	if ($hesk_settings['sequential'])
	{
    	$tmp = ' ('.$hesklang['seqid'].': '.$ticket['id'].')';
	}

	echo '

	<tr>
	<td>'.$hesklang['trackID'].': </td>
	<td>'.$trackingID.' '.$tmp.'</td>
    <td style="text-align:right">'.hesk_getAdminButtons().'</td>
	</tr>

	<tr>
	<td>'.$hesklang['created_on'].': </td>
	<td>'.hesk_date($ticket['dt']).'</td>
    <td>&nbsp;</td>
	</tr>

	<tr>
	<td>'.$hesklang['ticket_status'].': </td>
	<td>';

		$random=rand(10000,99999);

        $status_options = array(
        	0 => '<option value="0">'.$hesklang['open'].'</option>',
        	1 => '<option value="1">'.$hesklang['wait_reply'].'</option>',
            2 => '<option value="2">'.$hesklang['replied'].'</option>',
            4 => '<option value="4">'.$hesklang['in_progress'].'</option>',
            5 => '<option value="5">'.$hesklang['on_hold'].'</option>',
            3 => '<option value="3">'.$hesklang['closed'].'</option>',
        );

	    switch ($ticket['status'])
	    {
		    case 0:
		        echo '<font class="open">'.$hesklang['open'].'</font> [<a
		        href="change_status.php?track='.$trackingID.'&amp;s=3&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['close_action'].'</a>]';
                unset($status_options[0]);
		        break;
		    case 1:
		        echo '<font class="waitingreply">'.$hesklang['wait_reply'].'</font> [<a
		        href="change_status.php?track='.$trackingID.'&amp;s=3&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['close_action'].'</a>]';
                unset($status_options[1]);
		        break;
		    case 2:
		        echo '<font class="replied">'.$hesklang['replied'].'</font> [<a
		        href="change_status.php?track='.$trackingID.'&amp;s=3&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['close_action'].'</a>]';
                unset($status_options[2]);
		        break;
		    case 4:
		        echo '<font class="inprogress">'.$hesklang['in_progress'].'</font> [<a
		        href="change_status.php?track='.$trackingID.'&amp;s=3&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['close_action'].'</a>]';
                unset($status_options[2]);
		        break;
		    case 5:
		        echo '<font class="onhold">'.$hesklang['on_hold'].'</font> [<a
		        href="change_status.php?track='.$trackingID.'&amp;s=3&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['close_action'].'</a>]';
                unset($status_options[2]);
		        break;
		    default:
		        echo '<font class="resolved">'.$hesklang['closed'].'</font> [<a
		        href="change_status.php?track='.$trackingID.'&amp;s=1&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['open_action'].'</a>]';
                unset($status_options[3]);
	    }

	echo '
    </td>
    <td style="text-align:right">
    	<form style="margin-bottom:0;" action="change_status.php" method="post">
    	<i>'.$hesklang['chngstatus'].'</i>

        <span style="white-space:nowrap;">
        <select name="s">
	    <option value="-1" selected="selected">'.$hesklang['select'].'</option>
        ' . implode('', $status_options) . '
        </select>

	    <input type="submit" value="'.$hesklang['go'].'" class="orangebutton" onmouseover="hesk_btn(this,\'orangebuttonover\');" onmouseout="hesk_btn(this,\'orangebutton\');" /><input type="hidden" name="track" value="'.$trackingID.'" />
        <input type="hidden" name="token" value="'.hesk_token_echo(0).'" />
        </span>

        </form>
    </td>
	</tr>

	<tr>
	<td>'.$hesklang['last_update'].': </td>
	<td>'.hesk_date($ticket['lastchange']).'</td>
    <td>&nbsp;</td>
	</tr>

	<tr>
	<td>'.$hesklang['category'].': </td>
    <td>'.$category['name'].'</td>
	<td style="text-align:right">
    	<form style="margin-bottom:0;" action="move_category.php" method="post">
    	<i>'.$hesklang['move_to_catgory'].'</i>

        <span style="white-space:nowrap;">
        <select name="category">
	    <option value="-1" selected="selected">'.$hesklang['select'].'</option>
        '.$categories_options.'
        </select>

	    <input type="submit" value="'.$hesklang['go'].'" class="orangebutton" onmouseover="hesk_btn(this,\'orangebuttonover\');" onmouseout="hesk_btn(this,\'orangebutton\');" /><input type="hidden" name="track" value="'.$trackingID.'" />
        <input type="hidden" name="token" value="'.hesk_token_echo(0).'" />
        </span>

        </form>
	</td>
	</tr>

	<tr>
	<td>'.$hesklang['replies'].': </td>
	<td>'.$replies.'</td>
    <td>&nbsp;</td>
	</tr>

	<tr>
	<td>'.$hesklang['priority'].': </td>
    <td>';

        $options = array(
        	0 => '<option value="0">'.$hesklang['critical'].'</option>',
        	1 => '<option value="1">'.$hesklang['high'].'</option>',
            2 => '<option value="2">'.$hesklang['medium'].'</option>',
            3 => '<option value="3">'.$hesklang['low'].'</option>'
        );

        switch ($ticket['priority'])
        {
        	case 0:
            	echo '<font class="critical">'.$hesklang['critical'].'</font>';
                unset($options[0]);
                break;
        	case 1:
            	echo '<font class="important">'.$hesklang['high'].'</font>';
                unset($options[1]);
                break;
        	case 2:
            	echo '<font class="medium">'.$hesklang['medium'].'</font>';
                unset($options[2]);
                break;
        	default:
            	echo $hesklang['low'];
                unset($options[3]);
        }

	echo '
    </td>
	<td style="text-align:right">
    	<form style="margin-bottom:0;" action="priority.php" method="post">
        <i>'.$hesklang['change_priority'].'</i>

        <span style="white-space:nowrap;">
        <select name="priority">
        <option value="-1" selected="selected">'.$hesklang['select'].'</option>
        ';
        echo implode('',$options);
        echo '
        </select>

        <input type="submit" value="'.$hesklang['go'].'" class="orangebutton" onmouseover="hesk_btn(this,\'orangebuttonover\');" onmouseout="hesk_btn(this,\'orangebutton\');" /><input type="hidden" name="track" value="'.$trackingID.'" />
        <input type="hidden" name="token" value="'.hesk_token_echo(0).'" />
        </span>

        </form>
    </td>
	</tr>

	<tr>
	<td>'.$hesklang['last_replier'].': </td>
	<td>'.$ticket['repliername'].'</td>
    <td>&nbsp;</td>
	</tr>
    ';
	?>

	<tr>
	<td><?php echo $hesklang['owner']; ?>: </td>
	<td>
        <?php
        echo isset($admins[$ticket['owner']]) ? '<b>'.$admins[$ticket['owner']].'</b>' :
        	 ($can_assign_self ? '<b>'.$hesklang['unas'].'</b>'.' [<a href="assign_owner.php?track='.$trackingID.'&amp;owner='.$_SESSION['id'].'&amp;token='.hesk_token_echo(0).'">'.$hesklang['asss'].'</a>]' : '<b>'.$hesklang['unas'].'</b>');
        ?>
	</td>
    <td style="text-align:right">
    	<form style="margin-bottom:0;" action="assign_owner.php" method="post">
		<?php
        if (hesk_checkPermission('can_assign_others',0))
        {
			?>
			<i><?php echo $hesklang['asst']; ?></i>

            <span style="white-space:nowrap;">
            <select name="owner">
			<option value="" selected="selected"><?php echo $hesklang['select']; ?></option>
			<?php
            if ($ticket['owner'])
            {
            	echo '<option value="-1"> &gt; '.$hesklang['unas'].' &lt; </option>';
            }

			foreach ($admins as $k=>$v)
			{
				if ($k != $ticket['owner'])
				{
					echo '<option value="'.$k.'">'.$v.'</option>';
				}
			}
			?>
			</select>
			<input type="submit" value="<?php echo $hesklang['go']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
			<input type="hidden" name="track" value="<?php echo $trackingID; ?>" />
			<input type="hidden" name="token" value="<?php echo hesk_token_echo(0); ?>" />
            </span>
			<?php
        }
        ?>
        </form>
    </td>
	</tr>
	</table>

    <br />

	<table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
	<td><b><i><?php echo $hesklang['notes']; ?>:</i></b>

    <?php
    if ($can_reply)
    {
    ?>
    &nbsp; <a href="Javascript:void(0)" onclick="Javascript:hesk_toggleLayerDisplay('notesform')"><?php echo $hesklang['addnote']; ?></a>
    <?php
    }
    ?>
		<div id="notesform" style="display:none">
	    <form method="post" action="admin_ticket.php" style="margin:0px; padding:0px;">
	    <textarea name="notemsg" rows="6" cols="60"></textarea><br />
	    <input type="submit" value="<?php echo $hesklang['s']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /><input type="hidden" name="track" value="<?php echo $trackingID; ?>" />
        <i><?php echo $hesklang['nhid']; ?></i>
        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
        </form>
	    </div>
    </td>
	<td>&nbsp;</td>
	</tr>

	<?php
    $sql = 'SELECT t1.*, t2.`name` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'notes` AS t1 LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` AS t2 ON t1.`who` = t2.`id` WHERE `ticket`='.hesk_dbEscape($ticket['id']).' ORDER BY t1.`id` ASC';
	$res = hesk_dbQuery($sql);
	while ($note = hesk_dbFetchAssoc($res))
	{
	?>
    <tr>
    <td>
		<table border="0" width="100%" cellspacing="0" cellpadding="3">
		<tr>
	    <td class="notes"><i><?php echo $hesklang['noteby']; ?> <b><?php echo ($note['name'] ? $note['name'] : $hesklang['e_udel']); ?></b></i> - <?php echo hesk_date($note['dt']); ?><br /><img src="../img/blank.gif" border="0" width="5" height="5" alt="" /><br />
	    <?php echo $note['message']; ?></td>
	    </tr>
        </table>
    </td>
    <?php
    if ($can_del_notes || $note['who'] == $_SESSION['id'])
    {
	?>
		<td width="1" valign="top"><a href="admin_ticket.php?track=<?php echo $trackingID; ?>&amp;Refresh=<?php echo mt_rand(10000,99999); ?>&amp;delnote=<?php echo $note['id']; ?>&amp;token=<?php hesk_token_echo(); ?>" onclick="return hesk_confirmExecute('<?php echo $hesklang['delnote'].'?'; ?>');"><img src="../img/delete.png" alt="<?php echo $hesklang['delnote']; ?>" title="<?php echo $hesklang['delnote']; ?>" width="16" height="16" /></a></td>
	<?php
    }
    else
    {
    	echo '<td width="1" valign="top">&nbsp;</td>';
    }
	?>
    </tr>
    <?php
	}
    ?>

    </table>

    <!-- END TICKET HEAD -->
	</td>
	<td class="roundcornersright">&nbsp;</td>
</tr>
<tr>
	<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
</tr>
</table>

        <br />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>
    <!-- START TICKET REPLIES -->

		<table border="0" cellspacing="1" cellpadding="1" width="100%">
		<tr>
		<td class="ticketalt">

			<table border="0" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td valign="top">

			    <table border="0" cellspacing="1">
			    <tr>
			    <td><?php echo $hesklang['date']; ?>:</td>
			    <td><?php echo hesk_date($ticket['dt']); ?></td>
			    </tr>
			    <tr>
			    <td><?php echo $hesklang['name']; ?>:</td>
			    <td><?php echo $ticket['name']; ?></td>
			    </tr>
			    <tr>
			    <td><?php echo $hesklang['email']; ?>:</td>
			    <td><a href="mailto:<?php echo $ticket['email']; ?>"><?php echo $ticket['email']; ?></a></td>
			    </tr>
			    <tr>
			    <td><?php echo $hesklang['ip']; ?>:</td>
			    <td><?php echo $ticket['ip']; ?></td>
			    </tr>
			    </table>

			</td>
			<td style="text-align:right; vertical-align:top;">
            <?php echo hesk_getAdminButtons (); ?>
            </td>
			</tr>
			</table>

			<?php
			/* custom fields before message */
			$print_table = 0;
			$myclass = ' class="tickettd"';

			foreach ($hesk_settings['custom_fields'] as $k=>$v)
			{
				if ($v['use'] && $v['place']==0)
			    {
			    	if ($print_table == 0)
			        {
			        	echo '<table border="0" cellspacing="1" cellpadding="2">';
			        	$print_table = 1;
			        }

			        echo '
					<tr>
					<td valign="top" '.$myclass.'>'.$v['name'].':</td>
					<td valign="top" '.$myclass.'>'.$ticket[$k].'</td>
					</tr>
			        ';
			    }
			}
			if ($print_table)
			{
				echo '</table>';
			}
			?>

			<p><b><?php echo $hesklang['message']; ?>:</b></p>
			<p><?php echo $ticket['message']; ?><br />&nbsp;</p>

			<?php
			/* custom fields after message */
			$print_table = 0;

			foreach ($hesk_settings['custom_fields'] as $k=>$v)
			{
				if ($v['use'] && $v['place'])
			    {
			    	if ($print_table == 0)
			        {
			        	echo '<table border="0" cellspacing="1" cellpadding="2">';
			        	$print_table = 1;
			        }

			        echo '
					<tr>
					<td valign="top" '.$myclass.'>'.$v['name'].':</td>
					<td valign="top" '.$myclass.'>'.$ticket[$k].'</td>
					</tr>
			        ';
			    }
			}
			if ($print_table)
			{
				echo '</table>';
			}

            /* Print attachments */
            hesk_listAttachments($ticket['attachments']);
			?>

		</td>
		</tr>

		<?php
		$i=1;
		while ($reply = hesk_dbFetchAssoc($result))
		{

			if ($i) {$color='class="ticketrow"'; $i=0;}
			else {$color='class="ticketalt"'; $i=1;}
			$reply['dt']=hesk_date($reply['dt']);
			?>
		    <tr>
		    <td <?php echo $color; ?>>

            	<table border="0" cellspacing="0" cellpadding="0" width="100%">
                <tr>
                <td valign="top">
			        <table border="0" cellspacing="1">
			        <tr>
			        <td><?php echo $hesklang['date']; ?>:</td>
			        <td><?php echo $reply['dt']; ?></td>
			        </tr>
			        <tr>
			        <td><?php echo $hesklang['name']; ?>:</td>
			        <td><?php echo $reply['name']; ?></td>
			        </tr>
			        </table>
                </td>
                <td style="text-align:right; vertical-align:top;">
                <?php echo hesk_getAdminButtons(1,$i); ?>
                </td>
                </tr>
                </table>

		    <p><b><?php echo $hesklang['message']; ?>:</b></p>
		    <p><?php echo $reply['message']; ?></p>

        <?php

        hesk_listAttachments($reply['attachments'],$reply['id'],$i);

        /*

		if ($hesk_settings['attachments']['use'] && !empty($reply['attachments']))
		{
		    echo '<p><b>'.$hesklang['attachments'].':</b><br />';
		    $att=explode(',',substr($reply['attachments'], 0, -1));

			$tmp = $white ? 'White' : 'Blue';
			$style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';
			foreach ($att as $myatt)
			{
				list($att_id, $att_name) = explode('#', $myatt);
				echo '
				<a href="admin_ticket.php?a=delete_att&amp;att_id='.$att_id.'&amp;track='.$trackingID.'&amp;'.$tmp.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.$hesklang['pda'].'\');"><img src="../img/delete.png" width="16" height="16" alt="'.$hesklang['dela'].'" title="'.$hesklang['dela'].'" '.$style.' /></a>
				<a href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'"><img src="../img/clip.png" width="16" height="16" alt="'.$hesklang['dnl'].' '.$att_name.'" title="'.$hesklang['dnl'].' '.$att_name.'" '.$style.' /></a>
				<a href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'">'.$att_name.'</a><br />';
			}


		    foreach ($att as $myatt)
		    {
		        list($att_id, $att_name) = explode('#', $myatt);
		        echo '<img src="../img/clip.png" width="16" height="16" alt="'.$att_name.'" style="align:text-bottom" /><a href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'">'.$att_name.'</a><br />';
		    }
		    echo '</p>';
		}
        */

		if ($hesk_settings['rating'] && $reply['staffid'])
		{
			if ($reply['rating']==1)
		    {
		    	echo '<p class="rate">'.$hesklang['rnh'].'</p>';
		    }
		    elseif ($reply['rating']==5)
		    {
		    	echo '<p class="rate">'.$hesklang['rh'].'</p>';
		    }
		}

		echo '</td></tr>';
		}
		?>
        </table>

    <!-- END TICKET REPLIES -->
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
if ($can_reply)
{
?>
<!-- START REPLY FORM -->

<br /><hr />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

	<h3 align="center"><?php echo $hesklang['add_reply']; ?></h3>

	<script language="javascript" type="text/javascript"><!--
	var myMsgTxt = new Array();
	myMsgTxt[0]='';

	<?php
	/* CANNED RESPONSES */
	$can_options='';
	$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."std_replies` ORDER BY `reply_order` ASC";
	$result = hesk_dbQuery($sql);
	while ($mysaved=hesk_dbFetchRow($result))
	{
	    $can_options .= "<option value=\"$mysaved[0]\">$mysaved[1]</option>\n";
	    echo 'myMsgTxt['.$mysaved[0].']=\''.str_replace("\r\n","\\r\\n' + \r\n'", addslashes($mysaved[2]))."';\n";
	}

	?>

	function setMessage(msgid)
    {
		var myMsg=myMsgTxt[msgid];

        if (myMsg == '')
        {
        	if (document.form1.mode[0].checked)
            {
				document.getElementById('message').value = '';
            }
            return true;
        }

		myMsg = myMsg.replace(/%%HESK_NAME%%/g, '<?php echo hesk_jsString($ticket['name']); ?>');
		myMsg = myMsg.replace(/%%HESK_EMAIL%%/g, '<?php echo hesk_jsString($ticket['email']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom1%%/g, '<?php echo hesk_jsString($ticket['custom1']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom2%%/g, '<?php echo hesk_jsString($ticket['custom2']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom3%%/g, '<?php echo hesk_jsString($ticket['custom3']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom4%%/g, '<?php echo hesk_jsString($ticket['custom4']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom5%%/g, '<?php echo hesk_jsString($ticket['custom5']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom6%%/g, '<?php echo hesk_jsString($ticket['custom6']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom7%%/g, '<?php echo hesk_jsString($ticket['custom7']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom8%%/g, '<?php echo hesk_jsString($ticket['custom8']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom9%%/g, '<?php echo hesk_jsString($ticket['custom9']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom10%%/g, '<?php echo hesk_jsString($ticket['custom10']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom11%%/g, '<?php echo hesk_jsString($ticket['custom11']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom12%%/g, '<?php echo hesk_jsString($ticket['custom12']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom13%%/g, '<?php echo hesk_jsString($ticket['custom13']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom14%%/g, '<?php echo hesk_jsString($ticket['custom14']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom15%%/g, '<?php echo hesk_jsString($ticket['custom15']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom16%%/g, '<?php echo hesk_jsString($ticket['custom16']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom17%%/g, '<?php echo hesk_jsString($ticket['custom17']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom18%%/g, '<?php echo hesk_jsString($ticket['custom18']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom19%%/g, '<?php echo hesk_jsString($ticket['custom19']); ?>');
		myMsg = myMsg.replace(/%%HESK_custom20%%/g, '<?php echo hesk_jsString($ticket['custom20']); ?>');

	    if (document.getElementById)
        {
			if (document.getElementById('moderep').checked)
            {
				document.getElementById('HeskMsg').innerHTML='<textarea name="message" id="message" rows="12" cols="60">'+myMsg+'</textarea>';
            }
            else
            {
            	var oldMsg = document.getElementById('message').value;
		        document.getElementById('HeskMsg').innerHTML='<textarea name="message" id="message" rows="12" cols="60">'+oldMsg+myMsg+'</textarea>';
            }
	    }
        else
        {
			if (document.form1.mode[0].checked)
            {
				document.form1.message.value=myMsg;
            }
            else
            {
            	var oldMsg = document.form1.message.value;
		        document.form1.message.value=oldMsg+myMsg;
            }
	    }

	}
	//-->
	</script>

	<form method="post" action="admin_reply_ticket.php" enctype="multipart/form-data" name="form1">

    <br />

    <?php

    /* Ticket assigned to someone else? */
    if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && isset($admins[$ticket['owner']]))
    {
    	hesk_show_notice($hesklang['nyt'] . ' ' . $admins[$ticket['owner']]);
    }

    /* Ticket locked? */
    if ($ticket['locked'])
    {
    	hesk_show_notice($hesklang['tislock']);
    }

    /* Do we have any canned responses? */
    if (strlen($can_options))
    {
    ?>
    <div align="center">
    <table border="0">
    <tr>
    	<td>
	    <?php echo $hesklang['select_saved']; ?>:<br />
	    <select name="saved_replies" onchange="setMessage(this.value)">
		<option value="0"> - <?php echo $hesklang['select_empty']; ?> - </option>
		<?php echo $can_options; ?>
		</select><br />
	    <label><input type="radio" name="mode" id="moderep" value="0" checked="checked" /> <?php echo $hesklang['mrep']; ?></label><br />
	    <label><input type="radio" name="mode" id="modeadd" value="1" /> <?php echo $hesklang['madd']; ?></label>
        </td>
    </tr>
    </table>
    </div>
    <?php
    }
    ?>

	<p align="center"><?php echo $hesklang['message']; ?>: <font class="important">*</font><br />
	<span id="HeskMsg"><textarea name="message" id="message" rows="12" cols="60"><?php if (isset($_SESSION['ticket_message'])) {echo stripslashes(hesk_input($_SESSION['ticket_message']));} ?></textarea></span></p>

	<?php
	/* attachments */
	if ($hesk_settings['attachments']['use'])
    {
	?>
		<p align="center">

		<?php
		echo $hesklang['attachments'].':<br />';
		for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
		{
			echo '<input type="file" name="attachment['.$i.']" size="50" /><br />';
		}
		?>

		<?php echo$hesklang['accepted_types']; ?>: <?php echo '*'.implode(', *', $hesk_settings['attachments']['allowed_types']); ?><br />
		<?php echo $hesklang['max_file_size']; ?>: <?php echo $hesk_settings['attachments']['max_size']; ?> Kb
		(<?php echo sprintf("%01.2f",($hesk_settings['attachments']['max_size']/1024)); ?> Mb)

		</p>
	<?php
	}
	?>

	<div align="center">
	<center>
	<table>
	<tr>
	<td>
	<?php
    if ($ticket['owner'] != $_SESSION['id'] && $can_assign_self)
    {
		if (empty($ticket['owner']))
		{
			echo '<label><input type="checkbox" name="assign_self" value="1" checked="checked" /> <b>'.$hesklang['asss2'].'</b></label><br />';
		}
		else
		{
			echo '<label><input type="checkbox" name="assign_self" value="1" /> '.$hesklang['asss2'].'</label><br />';
		}
    }
	if ($ticket['status']==0||$ticket['status']==1||$ticket['status']==2)
	{
		echo '<label><input type="checkbox" name="close" value="1" /> '.$hesklang['close_this_ticket'].'</label><br />';
	}
	?>
	<label><input type="checkbox" name="set_priority" value="1" /> <?php echo $hesklang['change_priority']; ?> </label>
	<select name="priority">
	<?php echo implode('',$options); ?>
	</select><br />
	<label><input type="checkbox" name="signature" value="1" checked="checked" /> <?php echo $hesklang['attach_sign']; ?></label>
	(<a href="profile.php"><?php echo $hesklang['profile_settings']; ?></a>)
	</td>
	</tr>
	</table>
	</center>
	</div>

	<p align="center">
    <input type="hidden" name="orig_id" value="<?php echo $ticket['id']; ?>" />
    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
    <input type="submit" value="<?php echo $hesklang['submit_reply']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>

	</form>

	</td>
	<td class="roundcornersright">&nbsp;</td>
</tr>
<tr>
	<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
</tr>
</table>

<!-- END REPLY FORM -->
<?php
}

/* Display ticket history */
if (strlen($ticket['history']))
{
?>
<br /><hr />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

    	<h3><?php echo $hesklang['thist']; ?></h3>

		<ul><?php echo $ticket['history']; ?></ul>

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
}

/* Clear unneeded session variables */
hesk_cleanSessionVars('ticket_message');

require_once(HESK_PATH . 'inc/footer.inc.php');


/*** START FUNCTIONS ***/


function hesk_listAttachments($attachments='',$reply=0,$white=1)
{
	global $hesk_settings, $hesklang, $trackingID, $can_edit, $can_delete;

	/* Attachments disabled or not available */
	if ( ! $hesk_settings['attachments']['use'] || ! strlen($attachments) )
    {
    	return false;
    }

    /* Style and mousover/mousout */
    $tmp = $white ? 'White' : 'Blue';
    $style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';

	/* List attachments */
    $att=explode(',',substr($attachments, 0, -1));
	echo '<p><b>'.$hesklang['attachments'].':</b><br />';
	$att=explode(',',substr($attachments, 0, -1));
	foreach ($att as $myatt)
	{
		list($att_id, $att_name) = explode('#', $myatt);

        /* Can edit and delete tickets? */
        if ($can_edit && $can_delete)
        {
        	echo '<a href="admin_ticket.php?delatt='.$att_id.'&amp;reply='.$reply.'&amp;track='.$trackingID.'&amp;'.$tmp.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.$hesklang['pda'].'\');"><img src="../img/delete.png" width="16" height="16" alt="'.$hesklang['dela'].'" title="'.$hesklang['dela'].'" '.$style.' /></a> ';
        }

		echo '
		<a href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'"><img src="../img/clip.png" width="16" height="16" alt="'.$hesklang['dnl'].' '.$att_name.'" title="'.$hesklang['dnl'].' '.$att_name.'" '.$style.' /></a>
		<a href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'">'.$att_name.'</a><br />
        ';
	}
	echo '</p>';

    return true;
}


function hesk_getAdminButtons($reply=0,$white=1)
{
	global $hesk_settings, $hesklang, $ticket, $reply, $trackingID, $can_edit, $can_archive, $can_delete;

	$options = '';

    /* Style and mousover/mousout */
    $tmp = $white ? 'White' : 'Blue';
    $style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';

    /* Lock ticket button */
	if ( /* ! $reply && */ $can_edit)
	{
		if ($ticket['locked'])
		{
			$des = $hesklang['tul'] . ' - ' . $hesklang['isloc'];
            $options .= '<a href="lock.php?track='.$trackingID.'&amp;locked=0&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'"><img src="../img/unlock.png" width="16" height="16" alt="'.$des.'" title="'.$des.'" '.$style.' /></a> ';
		}
		else
		{
			$des = $hesklang['tlo'] . ' - ' . $hesklang['isloc'];
            $options .= '<a href="lock.php?track='.$trackingID.'&amp;locked=1&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'"><img src="../img/lock.png" width="16" height="16" alt="'.$des.'" title="'.$des.'" '.$style.' /></a> ';
		}
	}

	/* Tag ticket button */
	if ( /* ! $reply && */ $can_archive)
	{
		if ($ticket['archive'])
		{
        	$options .= '<a href="archive.php?track='.$trackingID.'&amp;archived=0&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'"><img src="../img/tag.png" width="16" height="16" alt="'.$hesklang['remove_archive'].'" title="'.$hesklang['remove_archive'].'" '.$style.' /></a> ';
		}
		else
		{
        	$options .= '<a href="archive.php?track='.$trackingID.'&amp;archived=1&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'"><img src="../img/tag_off.png" width="16" height="16" alt="'.$hesklang['add_archive'].'" title="'.$hesklang['add_archive'].'" '.$style.' /></a> ';
		}
	}

	/* Import to knowledgebase button */
	if ($hesk_settings['kb_enable'] && hesk_checkPermission('can_man_kb',0))
	{
		$options .= '<a href="manage_knowledgebase.php?a=import_article&amp;track='.$trackingID.'"><img src="../img/import_kb.png" width="16" height="16" alt="'.$hesklang['import_kb'].'" title="'.$hesklang['import_kb'].'" '.$style.' /></a> ';
	}

	/* Print ticket button */
    $options .= '<a href="../print.php?track='.$trackingID.'"><img src="../img/print.png" width="16" height="16" alt="'.$hesklang['printer_friendly'].'" title="'.$hesklang['printer_friendly'].'" '.$style.' /></a> ';

	/* Edit post */
	if ($can_edit)
	{
    	$tmp = $reply ? '&amp;reply='.$reply['id'] : '';
		$options .= '<a href="edit_post.php?track='.$trackingID.$tmp.'"><img src="../img/edit.png" width="16" height="16" alt="'.$hesklang['edtt'].'" title="'.$hesklang['edtt'].'" '.$style.' /></a> ';
	}


	/* Delete ticket */
	if ($can_delete)
	{
		if ($reply)
		{
			$url = 'admin_ticket.php';
			$tmp = 'delete_post='.$reply['id'];
			$img = 'delete.png';
			$txt = $hesklang['delt'];
		}
		else
		{
			$url = 'delete_tickets.php';
			$tmp = 'delete_ticket=1';
			$img = 'delete_ticket.png';
			$txt = $hesklang['dele'];
		}
		$options .= '<a href="'.$url.'?track='.$trackingID.'&amp;'.$tmp.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.$txt.'?\');"><img src="../img/'.$img.'" width="16" height="16" alt="'.$txt.'" title="'.$txt.'" '.$style.' /></a> ';
	}

    /* Return generated HTML */
    return $options;

} // END hesk_getAdminButtons()


function print_form()
{
	global $hesk_settings, $hesklang;
    global $trackingID;

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print admin navigation */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>

	</td>
	</tr>
	<tr>
	<td>

    &nbsp;<br />

	<?php
	/* This will handle error, success and notice messages */
	hesk_handle_messages();
	?>

	<div align="center">
	<table border="0" cellspacing="0" cellpadding="0" width="50%">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

	        <form action="admin_ticket.php" method="get">

	        <table width="100%" border="0" cellspacing="0" cellpadding="0">
	        <tr>
	                <td width="1"><img src="../img/existingticket.png" alt="" width="60" height="60" /></td>
	                <td>
	                <p><b><?php echo $hesklang['view_existing']; ?></a></b></p>
	                </td>
	        </tr>
	        <tr>
	                <td width="1">&nbsp;</td>
	                <td>&nbsp;</td>
	        </tr>
	        <tr>
	                <td width="1">&nbsp;</td>
	                <td>
	                <?php echo $hesklang['ticket_trackID']; ?>: <br /><input type="text" name="track" maxlength="20" size="35" value="<?php echo $trackingID; ?>" /><br />&nbsp;
	                </td>
	        </tr>
	        <tr>
	                <td width="1">&nbsp;</td>
	                <td><input type="submit" value="<?php echo $hesklang['view_ticket']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /><input type="hidden" name="Refresh" value="<?php echo rand(10000,99999); ?>"></td>
	        </tr>
	        </table>

	        </form>

		</td>
		<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
	</table>
	</div>

	<p>&nbsp;</p>
	<?php
	require_once(HESK_PATH . 'inc/footer.inc.php');
	exit();
} // End print_form()
?>
