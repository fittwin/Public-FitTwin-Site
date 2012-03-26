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

/* Is Knowledgebase enabled? */
if ( ! $hesk_settings['kb_enable'])
{
	hesk_error($hesklang['kbdis']);
}

/* Can this user manage Knowledgebase or just view it? */
$can_man_kb = hesk_checkPermission('can_man_kb',0);

/* Any category ID set? */
$catid = isset($_GET['category']) ? intval($_GET['category']) : 1;
$artid = isset($_GET['article']) ? intval($_GET['article']) : 0;

if (isset($_GET['search']))
{
	$query = hesk_input($_GET['search']);
}
else
{
	$query = 0;
}

$hesk_settings['kb_link'] = ($artid || $catid != 1 || $query) ? '<a href="knowledgebase_private.php" class="smaller">'.$hesklang['gopr'].'</a>' : ($can_man_kb ? $hesklang['gopr'] : '');

if ($hesk_settings['kb_search'] && $query)
{
    hesk_kb_search($query);
}
elseif ($artid)
{
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `id`=\''.hesk_dbEscape($artid).'\' AND `type`!=\'2\' LIMIT 1';
	$result  = hesk_dbQuery($sql);
    $article = hesk_dbFetchAssoc($result) or hesk_error($hesklang['kb_art_id']);
    hesk_show_kb_article($artid);
}
else
{
	hesk_show_kb_category($catid);
}

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/

function hesk_kb_header($kb_link) {
	global $hesk_settings, $hesklang, $can_man_kb;

	/* Print admin navigation */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>

	</td>
	</tr>
	<tr>
	<td>

	<span class="smaller">
    <?php
    if ($can_man_kb)
    {
	    ?>
	    <a href="manage_knowledgebase.php" class="smaller"><?php echo $hesklang['kb']; ?></a> &gt;
	    <?php
    }
    ?>
	<?php echo $kb_link; ?><br />&nbsp;</span>

	<!-- SUB NAVIGATION -->
	<?php show_subnav('view'); ?>
	<!-- SUB NAVIGATION -->

	<?php
	if ($hesk_settings['kb_search'])
	{
   		?>
		<br />
		<div style="text-align:center">
			<form action="knowledgebase_private.php" method="get" style="display: inline; margin: 0;" name="searchform">
			<span class="largebold"><?php echo $hesklang['ask']; ?></span> <input type="text" name="search" size="60" class="large" />
			<input type="submit" value="<?php echo $hesklang['shlp']; ?>" class="greenbutton" onmouseover="hesk_btn(this,'greenbuttonover');" onmouseout="hesk_btn(this,'greenbutton');" style="font-size:14px;height: 22px;" /><br />
			</form>
		</div>
		<br />

		<!-- START KNOWLEDGEBASE SUGGEST -->
			<div id="kb_suggestions" style="display:none">
			<img src="../img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo $hesklang['lkbs']; ?></i>
			</div>

			<script language="Javascript" type="text/javascript"><!--
			hesk_suggestKBsearch();
			//-->
			</script>
		<!-- END KNOWLEDGEBASE SUGGEST -->

		<br />
	    <?php
    } // End if KB search
    ?>

	</td>
	</tr>
	<tr>
	<td><?php
} // END hesk_kb_header()


function hesk_kb_search($query) {
	global $hesk_settings, $hesklang;

    define('HESK_NO_ROBOTS',1);

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');
	hesk_kb_header($hesk_settings['kb_link']);

	$sql = 'SELECT t1.* FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` AS t1 LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` AS t2 ON t1.`catid` = t2.`id`  WHERE t1.`type`!=\'2\' AND t2.`type`!=\'2\' AND MATCH(`subject`,`content`) AGAINST (\''.hesk_dbEscape($query).'\') LIMIT '.hesk_dbEscape($hesk_settings['kb_search_limit']);
	$res = hesk_dbQuery($sql);
    $num = hesk_dbNumRows($res);

    ?>
	<p>&raquo; <b><?php echo $hesklang['sr']; ?> (<?php echo $num; ?>)</b></p>

	<?php
	if ($num == 0)
	{
		echo '<p><i>'.$hesklang['nosr'].'</i></p>';
        hesk_show_kb_category(1,1);
	}
    else
    {
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>
		<div align="center">
        <table border="0" cellspacing="1" cellpadding="3" width="100%">
        <?php
			while ($article = hesk_dbFetchAssoc($res))
			{
	            $txt = strip_tags($article['content']);
	            if (strlen($txt) > $hesk_settings['kb_substrart'])
	            {
	            	$txt = substr(strip_tags($article['content']),0,$hesk_settings['kb_substrart']).'...';
	            }

	            if ($hesk_settings['kb_rating'])
	            {
	            	$alt = $article['rating'] ? sprintf($hesklang['kb_rated'], sprintf("%01.1f", $article['rating'])) : $hesklang['kb_not_rated'];
	                $rat = '<td width="1" valign="top"><img src="../img/star_'.(hesk_round_to_half($article['rating'])*10).'.png" width="85" height="16" alt="'.$alt.'" border="0" style="vertical-align:text-bottom" /></td>';
	            }
	            else
	            {
	            	$rat = '';
	            }

				echo '
				<tr>
				<td>
	                <table border="0" width="100%" cellspacing="0" cellpadding="1">
	                <tr>
	                <td width="1" valign="top"><img src="../img/article_text.png" width="16" height="16" border="0" alt="" style="vertical-align:middle" /></td>
	                <td valign="top"><a href="knowledgebase_private.php?article='.$article['id'].'">'.$article['subject'].'</a></td>
	                '.$rat.'
                    </tr>
	                </table>
	                <table border="0" width="100%" cellspacing="0" cellpadding="1">
	                <tr>
	                <td width="1" valign="top"><img src="../img/blank.gif" width="16" height="10" style="vertical-align:middle" alt="" /></td>
	                <td><span class="article_list">'.$txt.'</span></td>
                    </tr>
	                </table>

	            </td>
				</tr>';
			}
	?>
    	</table>
        </div>
	</td>
	<td class="roundcornersright">&nbsp;</td>
</tr>
<tr>
	<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
</tr>
</table>

    <p>&nbsp;<br />&lt;&lt; <a href="javascript:history.go(-1)"><?php echo $hesklang['back']; ?></a></p>
    <?php
    } // END else

} // END hesk_kb_search()


function hesk_show_kb_article($artid) {
	global $hesk_settings, $hesklang, $article;

	/* Print header */
    $hesk_settings['tmp_title'] = $article['subject'];
	require_once(HESK_PATH . 'inc/header.inc.php');
	hesk_kb_header($hesk_settings['kb_link']);

	$sql = 'SELECT `name`,`type` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` WHERE `id`=\''.hesk_dbEscape($article['catid']).'\' LIMIT 1';
	$result   = hesk_dbQuery($sql);
    $category = hesk_dbFetchAssoc($result) or hesk_error($hesklang['kb_cat_inv']);

    echo '<h1>'.$article['subject'].'</h1>

    <fieldset>
	<legend>'.$hesklang['as'].'</legend>
    '. $article['content'];

    if (!empty($article['attachments']))
    {
		echo '<p><b>'.$hesklang['attachments'].':</b><br />';
		$att=explode(',',substr($article['attachments'], 0, -1));
		foreach ($att as $myatt)
        {
			list($att_id, $att_name) = explode('#', $myatt);
			echo '<img src="../img/clip.png" width="16" height="16" alt="'.$att_name.'" style="align:text-bottom" /> <a href="../download_attachment.php?kb_att='.$att_id.'" rel="nofollow">'.$att_name.'</a><br />';
		}
		echo '</p>';
    }

    echo '</fieldset>';

    if ($article['catid']==1)
    {
    	$link = 'knowledgebase_private.php';
    }
    else
    {
    	$link = 'knowledgebase_private.php?category='.$article['catid'];
    }
    ?>

    <fieldset>
    <legend><?php echo $hesklang['ad']; ?></legend>
	<table border="0">
    <tr>
    <td><?php echo $hesklang['aid']; ?>: </td>
    <td><?php echo $article['id']; ?></td>
    </tr>
    <tr>
    <td><?php echo $hesklang['category']; ?>: </td>
    <td><a href="<?php echo $link; ?>"><?php echo $category['name']; ?></a></td>
    </tr>
    <tr>
    <td><?php echo $hesklang['dta']; ?>: </td>
    <td><?php echo hesk_date($article['dt']); ?></td>
    </tr>
    <tr>
    <td><?php echo $hesklang['views']; ?>: </td>
    <td><?php echo (isset($_GET['rated']) ? $article['views'] : $article['views']+1); ?></td>
    </tr>
    </table>
    </fieldset>

    <?php
    if (!isset($_GET['back']))
    {
    	?>
		<p>&nbsp;<br />&lt;&lt; <a href="javascript:history.go(-1)"><?php echo $hesklang['back']; ?></a></p>
        <?php
    }
    else
    {
    	?>
        <p>&nbsp;</p>
        <?php
    }

} // END hesk_show_kb_article()


function hesk_show_kb_category($catid, $is_search = 0) {
	global $hesk_settings, $hesklang;

    if ($is_search == 0)
    {
		/* Print header */
		require_once(HESK_PATH . 'inc/header.inc.php');
		hesk_kb_header($hesk_settings['kb_link']);

		if ($catid == 1)
	    {
	    	echo $hesklang['priv'];
	    }
    }

	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` WHERE `id`=\''.hesk_dbEscape($catid).'\' LIMIT 1';
	$res = hesk_dbQuery($sql);
    $thiscat = hesk_dbFetchAssoc($res) or hesk_error($hesklang['kb_cat_inv']);

	if ($thiscat['parent'])
	{
		$link = ($thiscat['parent'] == 1) ? 'knowledgebase_private.php' : 'knowledgebase_private.php?category='.$thiscat['parent'];
		echo '<span class="homepageh3">&raquo; '.$hesklang['kb_cat'].': '.$thiscat['name'].'</span>
        &nbsp;(<a href="javascript:history.go(-1)">'.$hesklang['back'].'</a>)
		';
	}

	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` WHERE `parent`=\''.hesk_dbEscape($catid).'\' AND `type`!=\'2\' ORDER BY `parent` ASC, `cat_order` ASC';
	$result = hesk_dbQuery($sql);
	if (hesk_dbNumRows($result) > 0)
	{
        ?>

		<p>&raquo; <b><?php echo $hesklang['kb_cat_sub']; ?>:</b></p>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

		<table border="0" cellspacing="1" cellpadding="3" width="100%">

		<?php
		$per_col = $hesk_settings['kb_cols'];
		$i = 1;

		while ($cat = hesk_dbFetchAssoc($result))
		{

			if ($i == 1)
		    {
				echo '<tr>';
		    }

            $private = ($cat['type'] == 1) ? ' *' : '';

			echo '
		    <td width="50%" valign="top">
			<table border="0">
			<tr><td><img src="../img/folder.gif" width="20" height="20" alt="" style="vertical-align:middle" /><a href="knowledgebase_private.php?category='.$cat['id'].'">'.$cat['name'].'</a>'.$private.'</td></tr>
			';

			/* Print two most popular articles */
			if ($hesk_settings['kb_numshow'] && $cat['articles'])
		    {
		    	$sql = 'SELECT `id`,`subject`,`type` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`=\''.hesk_dbEscape($cat['id']).'\' AND `type`!=\'2\' ORDER BY `views` DESC, `art_order` ASC LIMIT '.hesk_dbEscape(($hesk_settings['kb_numshow']+1));
		        $res = hesk_dbQuery($sql);
		        $num = 1;
				while ($art = hesk_dbFetchAssoc($res))
				{
                	$private = ($art['type'] == 1) ? ' *' : '';
					echo '
		            <tr>
		            <td><img src="../img/article_text.png" width="16" height="16" border="0" alt="" style="vertical-align:middle" />
		            <a href="knowledgebase_private.php?article='.$art['id'].'" class="article">'.$art['subject'].'</a>'.$private.'</td>
		            </tr>';

		            if ($num == $hesk_settings['kb_numshow'])
		            {
		            	break;
		            }
		            else
		            {
		            	$num++;
		            }
				}
		        if (hesk_dbNumRows($res) > $hesk_settings['kb_numshow'])
		        {
		        	echo '<tr><td>&raquo; <a href="knowledgebase_private.php?category='.$cat['id'].'"><i>'.$hesklang['m'].'</i></a></td></tr>';
		        }
		    }

			echo '
			</table>
		    </td>
			';

			if ($i == $per_col)
		    {
				echo '</tr>';
		        $i = 0;
		    }
			$i++;
		}
		/* Finish the table if needed */
		if ($i != 1)
		{
			for ($j=1;$j<=$per_col;$j++)
		    {
				echo '<td width="50%">&nbsp;</td>';
				if ($i == $per_col)
			    {
					echo '</tr>';
			        break;
			    }
		        $i++;
		    }
		}

		?>
		</table>

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
	} // END if NumRows > 0
	?>

	<p>&raquo; <b><?php echo $hesklang['ac']; ?></b></p>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

	<?php
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`=\''.hesk_dbEscape($catid).'\' AND `type`!=\'2\' ORDER BY `art_order` ASC';
	$res = hesk_dbQuery($sql);
	if (hesk_dbNumRows($res) == 0)
	{
		echo '<p><i>'.$hesklang['noac'].'</i></p>';
	}
	else
	{
			echo '<div align="center"><table border="0" cellspacing="1" cellpadding="3" width="100%">';
			while ($article = hesk_dbFetchAssoc($res))
			{
            	$private = ($article['type'] == 1) ? ' *' : '';

	            $txt = strip_tags($article['content']);
	            if (strlen($txt) > $hesk_settings['kb_substrart'])
	            {
	            	$txt = substr(strip_tags($article['content']),0,$hesk_settings['kb_substrart']).'...';
	            }

				echo '
				<tr>
				<td>
	                <table border="0" width="100%" cellspacing="0" cellpadding="1">
	                <tr>
	                <td width="1" valign="top"><img src="../img/article_text.png" width="16" height="16" border="0" alt="" style="vertical-align:middle" /></td>
	                <td valign="top"><a href="knowledgebase_private.php?article='.$article['id'].'">'.$article['subject'].'</a>'.$private.'</td>
                    </tr>
	                </table>
	                <table border="0" width="100%" cellspacing="0" cellpadding="1">
	                <tr>
	                <td width="1" valign="top"><img src="../img/blank.gif" width="16" height="10" style="vertical-align:middle" alt="" /></td>
	                <td><span class="article_list">'.$txt.'</span></td>
                    </tr>
	                </table>
	            </td>
				</tr>';
			}
		    echo '</table></div>';
	}
	?>

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
} // END hesk_show_kb_category()


function show_subnav($hide='') {
	global $hesk_settings, $hesklang, $can_man_kb;

    if ( ! $can_man_kb)
    {
    	echo '';
        return true;
    }

    $link['view'] = '<a href="knowledgebase_private.php"><img src="../img/view.png" width="16" height="16" alt="'.$hesklang['gopr'].'" title="'.$hesklang['gopr'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="knowledgebase_private.php">'.$hesklang['gopr'].'</a> | ';
    $link['newa'] = '<a href="manage_knowledgebase.php?a=add_article&amp;catid=1"><img src="../img/add_article.png" width="16" height="16" alt="'.$hesklang['kb_i_art'].'" title="'.$hesklang['kb_i_art'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_article&amp;catid=1">'.$hesklang['kb_i_art'].'</a> | ';
    $link['newc'] = '<a href="manage_knowledgebase.php?a=add_category&amp;parent=1"><img src="../img/add_category.png" width="16" height="16" alt="'.$hesklang['kb_i_cat'].'" title="'.$hesklang['kb_i_cat'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_category&amp;parent=1">'.$hesklang['kb_i_cat'].'</a> | ';

    if ($hide && isset($link[$hide]))
    {
    	$link[$hide] = preg_replace('#<a([^<]*)>#', '', $link[$hide]);
        $link[$hide] = str_replace('</a>','',$link[$hide]);
    }

	?>
	<form style="margin:0px;padding:0px;" method="get" action="manage_knowledgebase.php">
    <?php
    echo $link['view'];
    echo $link['newa'];
    echo $link['newc'];
    ?>
	<img src="../img/edit.png" width="16" height="16" alt="<?php echo $hesklang['edit']; ?>" title="<?php echo $hesklang['edit']; ?>" border="0" style="border:none;vertical-align:text-bottom" /></a> <input type="hidden" name="a" value="edit_article" /><?php echo $hesklang['aid']; ?>: <input type="text" name="id" size="3" /> <input type="submit" value="<?php echo $hesklang['edit']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
	</form>

	<?php
} // End show_subnav()
?>
