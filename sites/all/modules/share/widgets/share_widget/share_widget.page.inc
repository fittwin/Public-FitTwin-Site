<?php
// $Id: share_widget.page.inc,v 1.1.2.1 2009/03/26 21:12:22 greenskin Exp $

/**
 * @file
 * This file is used to build the Share page.
 *
 * File is loaded when the uri begins with 'share'.
 *
 * Written by greenSkin.
 */

/*******************************************************************************
 * Callback Functions, Forms, and Tables
 ******************************************************************************/

function share_widget_page($share, $node) {
  drupal_set_title($share->label);

  $tabs = array();
  foreach ($share->tabs as $tab) {
    if ($tab['enabled']) {
      $tabs[$tab['module']] = $share->render_tab($tab['module'], $node);
    }
  }
  $content = node_view($node, FALSE, FALSE, FALSE);

  return theme('share_widget_page', $tabs, $content);
}

/*******************************************************************************
 * Theme Functions
 ******************************************************************************/

function theme_share_widget_page($tabs, $content) {
  drupal_add_css(drupal_get_path('module', 'share') .'/css/share_page.css');

  $output = '<div id="share-tabs">';
  foreach ($tabs as $module => $tab) {
    $output .= '<div id="share-'. $tab['module'] .'" class="share-tab">'. $tab .'</div>';
  }
  $output .= '<div class="clear-block"></div></div>';
  $output .= $content;
  return $output;
}
