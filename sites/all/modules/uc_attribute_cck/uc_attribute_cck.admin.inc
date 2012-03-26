<?php
// $Id: uc_attribute_cck.admin.inc,v 1.4 2011/02/07 12:03:20 trungonly Exp $

/**
 * @file
 * Admin interface for Attributes CCK.
 *
 */


/**
 * Form to associate attributes with products or classes.
 *
 */
function uc_attributes_cck_form($form_state, $object, $type = 'product') {

  switch ($type) {
  case 'product':
    $product = &$object;
    $nid = $product->nid;
    if (empty($product->title)) {
      drupal_goto('node/'. $nid);
    }

    drupal_set_title(check_plain($product->title));

    $default_value = $product->attributes_cck['types'];

    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $nid,
    );
    break;
  case 'class':
    $class = &$object;
    if (empty($class->name)) {
      drupal_goto('admin/store/products/classes/'. $class->pcid);
    }

    drupal_set_title(check_plain($class->name));

    $uc_class_get_attributes_cck = uc_class_get_attributes_cck($class->pcid);
    $default_value = $uc_class_get_attributes_cck['types'];

    $form['pcid'] = array(
      '#type' => 'value',
      '#value' => $class->pcid,
    );
    break;
  }

  // add form
  $form['content_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Content types'),
    '#default_value' => $default_value,
    '#options' => array_map('check_plain', node_get_types('names')),
    '#description' => t("Select content types to use CCK fields as attribute for this $type."),
  );

  $form['type'] = array(
    '#type' => 'value',
    '#value' => $type,
  );

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));

  return $form;
}

/**
 * uc_attributes_cck_form submit handler.
 *
 * @see uc_attributes_cck_form()
 */
function uc_attributes_cck_form_submit(&$form, &$form_state) {
  // Fix up the types array to remove unchecked types.
  $form_state['values']['content_types'] = array_filter($form_state['values']['content_types']);

  $edit = &$form_state['values'];

  $table = ($edit['type'] == 'product' ? 'uc_attribute_cck_node_types' : 'uc_attribute_cck_class_types');
  $field = ($edit['type'] == 'product' ? 'nid' : 'pcid');
  $id = ($edit['type'] == 'product' ? $edit['nid'] : $edit['pcid']);

  db_query("DELETE FROM {" . $table . "} WHERE $field = %d", $id);
  
  if (!count($edit['content_types']) && $edit['type'] == 'product') {   // add an empty type to override product class definition
    db_query("INSERT INTO {" . $table . "} ($field, type) VALUES ('%s', '%s')", $id, '');
  }
  else {
    foreach ($edit['content_types'] as $content_type => $selected) {
      db_query("INSERT INTO {" . $table . "} ($field, type) VALUES ('%s', '%s')", $id, $content_type);
    }
  }
  
  drupal_set_message(t('Configuration saved.'));
}

