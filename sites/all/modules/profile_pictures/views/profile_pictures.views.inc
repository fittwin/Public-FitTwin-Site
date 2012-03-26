<?php
/**
 * @file
 *   Views' hooks' implementations
 */

/**
 * Implementation of hook_views_handlers().
 */
function profile_pictures_views_handlers() {
  $items = array();
  $items['info'] = array(
    'path' => drupal_get_path('module', 'profile_pictures') . '/views/',
  );
  $items['handlers'] = array(
    'profile_pictures_field' => array(
      'parent' => 'views_handler_field',
    ),
  );
  return $items;
}

/**
 * Implementation of hook_views_data().
 */
function profile_pictures_views_data() {
  $data = array();
  $fields = profile_views_get_fields();
  foreach ($fields as $field) {
    if ($field->type != 'picture') continue;

    $table_name = 'profile_values_' . $field->name;
    $data[$table_name]['value'] = array(
      'field' => array(
        'title' => $field->title,
        'help' => 'Picture field',
        'handler' => 'profile_pictures_field',
      ),
    );
  }
  return $data;
}
