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

/* What should we do? */
$_REQUEST['a'] = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
switch ($_REQUEST['a'])
{
    case 'do_login':
    	do_login();
        break;
    case 'login':
    	print_login();
        break;
    case 'logout':
    	logout();
        break;
    default:
    	hesk_autoLogin();
    	print_login();
}

/* Print footer */
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();

/*** START FUNCTIONS ***/
function do_login() {
	global $hesk_settings, $hesklang;

    $hesk_error_buffer = array();

    $user = hesk_input($_POST['user']);
    if (empty($user))
    {
		$myerror = $hesk_settings['list_users'] ? $hesklang['select_username'] : $hesklang['enter_username'];
        $hesk_error_buffer['user'] = $myerror;
    }
    define('HESK_USER', $user);

	$pass = hesk_input($_POST['pass']);
	if (empty($pass))
	{
    	$hesk_error_buffer['pass'] = $hesklang['enter_pass'];
	}

	if ($hesk_settings['secimg_use'] == 2 && !isset($_SESSION['img_a_verified']))
	{
		$mysecnum = isset($_POST['mysecnum']) ? hesk_isNumber($_POST['mysecnum']) : 0;
		if (empty($mysecnum))
		{
        	$hesk_error_buffer['mysecnum'] = $hesklang['sec_miss'];
		}
		else
		{
			require(HESK_PATH . 'inc/secimg.inc.php');
			$sc = new PJ_SecurityImage($hesk_settings['secimg_sum']);
			if ($sc->checkCode($mysecnum,$_SESSION['checksum']))
			{
            	$_SESSION['img_a_verified']=true;
            }
            else
            {
            	$hesk_error_buffer['mysecnum'] = $hesklang['sec_wrng'];
			}
		}
	}

    /* Any missing fields? */
	if (count($hesk_error_buffer)!=0)
	{
    	$_SESSION['a_iserror'] = array_keys($hesk_error_buffer);

	    $tmp = '';
	    foreach ($hesk_error_buffer as $error)
	    {
	        $tmp .= "<li>$error</li>\n";
	    }
	    $hesk_error_buffer = $tmp;

	    $hesk_error_buffer = $hesklang['pcer'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
	    hesk_process_messages($hesk_error_buffer,'NOREDIRECT');
        print_login();
        exit();
	}
    elseif (isset($_SESSION['img_a_verified']))
    {
		unset($_SESSION['img_a_verified']);
    }

	/* User entered all required info, now lets limit brute force attempts */
	hesk_limitBfAttempts();

	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` WHERE `user` = \''.hesk_dbEscape($user).'\' LIMIT 1';
	$result = hesk_dbQuery($sql);
	if (hesk_dbNumRows($result) != 1)
	{
    	$_SESSION['a_iserror'] = array('user','pass');
    	hesk_process_messages($hesklang['wrong_user'],'NOREDIRECT');
        print_login();
        exit();
	}

	$res=hesk_dbFetchAssoc($result);
	foreach ($res as $k=>$v)
	{
	    $_SESSION[$k]=$v;
	}

	/* Check password */
	if (hesk_Pass2Hash($pass) != $_SESSION['pass'])
    {
    	$_SESSION['a_iserror'] = array('pass');
		hesk_process_messages($hesklang['wrong_pass'],'NOREDIRECT');
		print_login();
		hesk_session_stop();
		exit();
	}

    $pass_enc = hesk_Pass2Hash($_SESSION['pass'].strtolower($user).$_SESSION['pass']);

    /* Check if default password */
    if ($_SESSION['pass'] == '499d74967b28a841c98bb4baaabaad699ff3c079')
    {
    	hesk_process_messages($hesklang['chdp'],'NOREDIRECT','NOTICE');
    }

	unset($_SESSION['pass']);

	/* Login successful, clean brute force attempts */
	hesk_cleanBfAttempts();

	/* Regenerate session ID (security) */
	hesk_session_regenerate_id();

	/* Get allowed categories */
	if (empty($_SESSION['isadmin']))
	{
	    //$cat=substr($_SESSION['categories'], 0, -1);
	    $_SESSION['categories']=explode(',',$_SESSION['categories']);
	}

	#session_write_close();

	/* Remember username? */
	if ($hesk_settings['autologin'] && $_POST['remember_user']=='AUTOLOGIN')
	{
		setcookie('hesk_username', "$user", strtotime('+1 year'));
		setcookie('hesk_p', "$pass_enc", strtotime('+1 year'));
	}
	elseif ($_POST['remember_user']=='JUSTUSER')
	{
		setcookie('hesk_username', "$user", strtotime('+1 year'));
		setcookie('hesk_p', '');
	}
	else
	{
		// Expire cookie if set otherwise
		setcookie('hesk_username', '');
		setcookie('hesk_p', '');
	}

    /* Close any old tickets here so Cron jobs aren't necessary */
	if ($hesk_settings['autoclose'])
    {
    	$revision = sprintf($hesklang['thist3'],hesk_date(),$hesklang['auto']);
    	$dt  = date('Y-m-d H:i:s',time() - $hesk_settings['autoclose']*86400);
		$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` SET `status`=\'3\', `history`=CONCAT(`history`,\''.hesk_dbEscape($revision).'\')  WHERE `status` = \'2\' AND `lastchange` <= \''.hesk_dbEscape($dt).'\'';
		hesk_dbQuery($sql);
    }

	/* Redirect to the destination page */
	if (isset($_REQUEST['goto']))
	{
    	$url = hesk_input($_REQUEST['goto']);
	    $url = str_replace('&amp;','&',$url);

        /* goto parameter can be set to the local domain only */
        $myurl = parse_url($hesk_settings['hesk_url']);
        $goto  = parse_url($url);

        if (isset($myurl['host']) && isset($goto['host']))
        {
        	if ( str_replace('www.','',strtolower($myurl['host'])) != str_replace('www.','',strtolower($goto['host'])) )
            {
            	$url = 'admin_main.php';
            }
        }

	    header('Location: '.$url);
	}
	else
	{
	    header('Location: admin_main.php');
	}
	exit();
} // End do_login()


function print_login() {
	global $hesk_settings, $hesklang;
    $hesk_settings['tmp_title'] = $hesk_settings['hesk_title'] . ' - ' .$hesklang['admin_login'];
	require_once(HESK_PATH . 'inc/header.inc.php');

	if (isset($_REQUEST['notice']))
	{
    	hesk_process_messages($hesklang['session_expired'],'NOREDIRECT');
	}

    if (!isset($_SESSION['a_iserror']))
    {
    	$_SESSION['a_iserror'] = array();
    }

	?>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td width="3"><!--img src="../img/headerleftsm.jpg" width="3" height="25" alt="" /--></td>
	<td class="headersm"><?php echo $hesklang['login']; ?></td>
	<td width="3"><!--img src="../img/headerrightsm.jpg" width="3" height="25" alt="" /--></td>
	</tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="3">
	<tr>
	<td><span class="smaller"><a href="<?php echo $hesk_settings['site_url']; ?>" class="smaller"><?php echo $hesk_settings['site_title']; ?></a> &gt;
	<?php echo $hesklang['admin_login']; ?></span></td>
	</tr>
	</table>

	</td>
	</tr>
	<tr>
	<td>

	<br />

	<?php
	/* This will handle error, success and notice messages */
	hesk_handle_messages();
	?>

    <br />

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

        <form action="index.php" method="post" name="form1">

        <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
                <td width="60" style="text-align:center"><img src="../img/login.png" alt="" width="24" height="24" /></td>
                <td>
                <p><b><?php echo $hesklang['admin_login']; ?></a></b></p>
                </td>
        </tr>
        <tr>
                <td width="60">&nbsp;</td>
                <td>&nbsp;</td>
        </tr>
        <tr>
                <td width="60">&nbsp;</td>
                <td><?php echo $hesklang['username']; ?>:<br />
				<?php

                $cls = in_array('user',$_SESSION['a_iserror']) ? ' class="isError" ' : '';

				if (defined('HESK_USER'))
				{
					$savedUser = HESK_USER;
				}
				else
				{
					$savedUser = isset($_COOKIE['hesk_username']) ? htmlspecialchars($_COOKIE['hesk_username']) : '';
				}

		        $is_1 = '';
		        $is_2 = '';
		        $is_3 = '';

				if ($hesk_settings['autologin'] && (isset($_COOKIE['hesk_p']) || (isset($_POST['remember_user']) && $_POST['remember_user'] == 'AUTOLOGIN') ) )
		        {
		        	$is_1 = 'checked="checked"';
		        }
		        elseif (isset($_COOKIE['hesk_username']) || (isset($_POST['remember_user']) && $_POST['remember_user'] == 'JUSTUSER') )
		        {
		        	$is_2 = 'checked="checked"';
		        }
		        else
		        {
		        	$is_3 = 'checked="checked"';
		        }

				if ($hesk_settings['list_users'])
				{
				    echo '<select name="user" '.$cls.'>';
				    $sql    = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'users` ORDER BY `id` ASC';
				    $result = hesk_dbQuery($sql);
				    while ($row=hesk_dbFetchAssoc($result))
				    {
				        $sel = (strtolower($savedUser) == strtolower($row['user'])) ? 'selected="selected"' : '';
				        echo '<option value="'.$row['user'].'" '.$sel.'>'.$row['user'].'</option>';
				    }
				    echo '</select>';

				}
				else
				{
				    echo '<input type="text" name="user" size="35" value="'.$savedUser.'" '.$cls.' />';
				}
				?>
                </td>
        </tr>
        <tr>
                <td width="60">&nbsp;</td>
                <td>&nbsp;</td>
        </tr>
        <tr>
                <td width="60">&nbsp;</td>
                <td><?php echo $hesklang['pass']; ?>:<br /><input type="password" name="pass" size="35" <?php if (in_array('pass',$_SESSION['a_iserror'])) {echo ' class="isError" ';} ?> /></td>
        </tr>
		<?php
		if ($hesk_settings['secimg_use'] == 2)
	    {
			?>
			<tr>
				<td width="60">&nbsp;</td>
				<td>
                <hr />
				<?php
	            if (isset($_SESSION['img_a_verified']))
	            {
					echo '<img src="../img/success.png" width="16" height="16" border="0" alt="" style="vertical-align:text-bottom" /> '.$hesklang['vrfy'];
	            }
	            else
	            {
                	$cls = in_array('mysecnum',$_SESSION['a_iserror']) ? ' class="isError" ' : '';

		            echo $hesklang['sec_enter'].'<br />&nbsp;<br /><img src="../print_sec_img.php?'.rand(10000,99999).'" width="150" height="40" alt="'.$hesklang['sec_img'].'" title="'.$hesklang['sec_img'].'" border="1" name="secimg" style="vertical-align:text-bottom" /> '.
		            '<a href="javascript:void(0)" onclick="javascript:document.form1.secimg.src=\'../print_sec_img.php?\'+ ( Math.floor((90000)*Math.random()) + 10000);"><img src="../img/reload.png" height="24" width="24" alt="'.$hesklang['reload'].'" title="'.$hesklang['reload'].'" border="0" style="vertical-align:text-bottom" /></a>'.
		            '<br />&nbsp;<br /><input type="text" name="mysecnum" size="20" maxlength="5" '.$cls.' />';
                }
                ?>
                <hr />
				</td>
			</tr>
            <?php
		}
        else
        {
        	?>
	        <tr>
	                <td width="60">&nbsp;</td>
	                <td>&nbsp;</td>
	        </tr>
	        <?php
        } // End if $hesk_settings['secimg_use'] == 2

		if ($hesk_settings['autologin'])
		{
			?>
			<tr>
				<td width="60">&nbsp;</td>
				<td><label><input type="radio" name="remember_user" value="AUTOLOGIN" <?php echo $is_1; ?> /> <?php echo $hesklang['autologin']; ?></label><br />
				<label><input type="radio" name="remember_user" value="JUSTUSER" <?php echo $is_2; ?> /> <?php echo $hesklang['just_user']; ?></label><br />
				<label><input type="radio" name="remember_user" value="NOTHANKS" <?php echo $is_3; ?> /> <?php echo $hesklang['nothx']; ?></label></td>
			</tr>
			<?php
		}
		else
		{
			?>
			<tr>
				<td width="60">&nbsp;</td>
				<td><label><input type="checkbox" name="remember_user" value="JUSTUSER" <?php echo $is_2; ?> /> <?php echo $hesklang['remember_user']; ?></label></td>
			</tr>
			<?php
		} // End if $hesk_settings['autologin']
		?>
        <tr>
                <td width="60">&nbsp;</td>
                <td>&nbsp;</td>
        </tr>
        <tr>
                <td width="60">&nbsp;</td>
				<td><input type="submit" value="<?php echo $hesklang['click_login']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
                <input type="hidden" name="a" value="do_login" />
				<?php
				if (isset($_REQUEST['goto']) && $url=hesk_input($_REQUEST['goto']))
				{
					echo '<input type="hidden" name="goto" value="'.$url.'" />';
				}
				?>
                <br />&nbsp;
				</td>
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
	hesk_cleanSessionVars('a_iserror');

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // End print_login()


function logout() {
	global $hesk_settings, $hesklang;

    if ( ! hesk_token_check($_GET['token'],0))
    {
		print_login();
        exit();
    }

    /* Delete from Who's online database */
	if ($hesk_settings['online'])
	{
    	require(HESK_PATH . 'inc/users_online.inc.php');
		hesk_setOffline($_SESSION['id']);
	}
    /* Destroy session and cookies */
	hesk_session_stop();

    /* If we're using the security image for admin login start a new session */
	if ($hesk_settings['secimg_use'] == 2)
    {
    	hesk_session_start();
    }

	/* Show success message and reset the cookie */
    hesk_process_messages($hesklang['logout_success'],'NOREDIRECT','SUCCESS');
    setcookie('hesk_p', '');

    /* Print the login form */
	print_login();
	exit();
} // End logout()

?>
