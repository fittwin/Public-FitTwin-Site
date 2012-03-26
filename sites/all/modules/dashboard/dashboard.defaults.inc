<?php
// $Id: dashboard.defaults.inc,v 1.9 2010/05/22 15:02:20 techsoldaten Exp $

/**
 * @file
 * Dashboard module defaults file.
 * Stores default content options for dashboards.
 */

function dashboard_defaults($uid) {
  $page = array(
    'uid' => $uid,
    'path' => 'dashboard',
    'weight' => 0,
    'title' => 'Dashboard',
  );
  drupal_write_record('dashboard_page', $page);

  $widgets = array(
    array(
      'widget_id' => '1',
      'page_id' => $page['page_id'],
      'type' => 'block',
      'subtype' => 'comment-0',
      'col' => 0,
      'weight' => 1,
    ),
  );

  foreach ($widgets as $widget) {
    drupal_write_record('dashboard_widget', $widget);
  }

}
