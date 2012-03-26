<?php 
// $Id: template.php,v 1.1.4.3 2008/07/13 05:53:13 couzinhub Exp $
  

/**
 * Adding a title to the comment section.
 */

function phptemplate_comment_wrapper($content, $type = null) {
  static $node_type;
  if (isset($type)) $node_type = $type;
    return '<h2 id="comments">'. t('Comments') .'</h2>'. $content ;
  
}

/**
 * Adding a span tag in tabs for theming purposes
 */
function phptemplate_menu_item_link($link) {
  if (empty($link['options'])) {
    $link['options'] = array();
  }

  // If an item is a LOCAL TASK, render it as a tab
  if ($link['type'] & MENU_IS_LOCAL_TASK) {
    $link['title'] = '<span class="tab">' . check_plain($link['title']) . '</span>';
    $link['options']['html'] = TRUE;
  }

  if (empty($link['type'])) {
    $true = TRUE;
  }

  return l($link['title'], $link['href'], $link['options']);
}
