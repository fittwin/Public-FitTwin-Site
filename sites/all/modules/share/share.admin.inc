<?php
// $Id: share.admin.inc,v 1.1.2.4 2009/03/26 21:12:20 greenskin Exp $

/**
 * @file
 * Functions for administering Share.
 *
 * This file holds the functions that build and theme the Share administration
 * pages. File is loaded when the uri begins with 'admin/build/share'.
 *
 * Written by greenSkin.
 */

/*******************************************************************************
 * Callback Functions, Forms, and Tables
 ******************************************************************************/

function share_admin_page() {
  $result = db_query("SELECT * FROM {share}");
  $widgets = array();
  while ($data = db_fetch_object($result)) {
    $node_types = db_query("SELECT node_type FROM {share_node_types} WHERE share_id = %d", $data->share_id);
    $data->node_types = array();
    while ($type = db_fetch_object($node_types)) {
      $data->node_types[$type->node_type] = $type->node_type;
    }
    $widgets[$data->module][] = $data;
  }

  if (empty($widgets)) {
    return t('No widgets have yet been defined.');
  }

  ksort($widgets);
  return theme('share_list_widgets', $widgets);
}

function share_add_form($form_state) {
  $form = array();
  $widgets = module_implements('share_render');

  // If only one widget is installed use it.
  if (count($widgets) == 1) {
    $form_state['values'] = array('module' => $widgets[0]);
    share_add_form_submit(array(), $form_state);
    drupal_goto($form_state['redirect']);
    exit();
  }

  $options;
  foreach ($widgets as $module) {
    $info = module_invoke($module, 'share_info');
    if (isset($info['widget']) && !empty($info['widget'])) {
      $options[$module] = $info['name'];
    }
  }
  asort($options);
  $form['module'] = array(
    '#type' => 'select',
    '#title' => t('Select type'),
    '#options' => $options
  );
  $form['continue'] = array(
    '#type' => 'submit',
    '#value' => t('Continue')
  );
  return $form;
}

function share_add_form_submit($form, &$form_state) {
  $module = $form_state['values']['module'];

  share_include_class($module);

  $share = new $module();
  $share->defaults();

  $share_id = share_save($share);
  $form_state['redirect'] = 'admin/build/share/'. $share_id .'/edit';
}

function share_form($form_state, $share) {
  drupal_set_title($share->label);

  $form = array();
  $form['_share'] = array('#type' => 'value', '#value' => $share);
  $form['share_id'] = array(
    '#type' => 'hidden',
    '#value' => $share->share_id
  );
  $form['status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable this widget'),
    '#default_value' => $share->status
  );
  $form['module'] = array(
    '#type' => 'hidden',
    '#value' => $share->module
  );
  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#description' => t('Give a label for this widget.'),
    '#default_value' => $share->label,
    '#required' => TRUE
  );

  // WHERE
  $form['where_wrapper'] = array(
    '#type' => 'fieldset',
    '#title' => t('Where to share'),
    '#description' => t('Set node credentials for where you would like the widget to show.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );
  $form['where_wrapper']['node_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node types'),
    '#description' => t('If no node type is selected, the widget will not be restricted by node type.'),
    '#default_value' => $share->node_types,
    '#options' => array_map('check_plain', node_get_types('names'))
  );
  if (module_exists('taxonomy')) {
    $terms = taxonomy_form_all();
    if (!empty($terms)) {
      $form['where_wrapper']['terms'] = array(
        '#type' => 'select',
        '#multiple' => TRUE,
        '#title' => t('Categories'),
        '#description' => t('If no category term is selected, the widget will not be restricted by terms.'),
        '#default_value' => $share->terms,
        '#options' => taxonomy_form_all()
      );
    }
  } else {
    $form['where_wrapper']['terms'] = array(
      '#type' => 'hidden',
      '#value' => array()
    );
  }
  $form['where_wrapper']['type'] = array(
    '#type' => 'select',
    '#title' => t('Where to display'),
    '#default_value' => $share->type,
    '#options' => array('block' => t('Block'), 'link' => t('Link'), 'node' => t('Node'))
  );
  $form['where_wrapper']['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#description' => t('Only applies if the widget is shown in the node.'),
    '#default_value' => $share->weight
  );
  $form['where_wrapper']['view'] = array(
    '#type' => 'select',
    '#title' => t('View'),
    '#default_value' => $share->view,
    '#options' => array(t('Teasers'), t('Full-page'), t('Teasers and full-page'))
  );

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 10,
    '#submit' => array('share_form_submit', 'share_form_submit_redirect')
  );
  if ($share->share_id) {
    $form['save_edit'] = array(
      '#type' => 'submit',
      '#value' => t('Save and edit'),
      '#weight' => 11
    );
  }

  return $form;
}

function share_form_submit($form, &$form_state) {
  $share_id = share_save((object)$form_state['values']);
  if (isset($form_state['values']['share_id'])) {
    drupal_set_message(t('@label saved.', array('@label' => $form_state['values']['label'])));
  } else {
    drupal_set_message(t('@label created.', array('@label' => $form_state['values']['label'])));
  }

  // Rebuild blocks
  _block_rehash();
}

function share_form_submit_redirect($form, &$form_state) {
  $form_state['redirect'] = 'admin/build/share';
}

function share_status($state, $share_id, $redirect = FALSE) {
  switch ($state) {
    case 'disable':
      $status = 0;
      break;
    case 'enable':
      $status = 1;
      break;
  }
  db_query("UPDATE {share} SET status = %d WHERE share_id = %d", $status, $share_id);

  if ($redirect) {
    drupal_goto('admin/build/share');
  }
}

function share_delete_form($form_state, $share) {
  drupal_set_title($share->label);

  $form = array();
  $form['share'] = array(
    '#type' => 'value',
    '#value' => $share
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete %title?', array('%title' => $share->label)),
    'admin/build/share',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

function share_delete_form_submit($form, &$form_state) {
  $share = (object)$form_state['values']['share'];
  share_delete($share);

  // Rebuild blocks
  _block_rehash();

  drupal_set_message(t('@label deleted.', array('@label' => $share->label)));
  $form_state['redirect'] = 'admin/build/share';
}

/*******************************************************************************
 * Module and Helper Functions
 ******************************************************************************/



/*******************************************************************************
 * Theme Functions
 ******************************************************************************/

function theme_share_list_widgets($widgets) {
  $headers = array(
    array('data' => t('Label')),
    array('data' => t('Node types')),
    array('data' => t('Actions'))
  );

  $output = '';
  foreach ($widgets as $module => $value) {
    $rows = array();
    foreach ($value as $share) {
      $types = array();
      foreach ($share->node_types as $type) {
        if ($type) {
          $types[] = node_get_types('name', $type);
        }
      }

      $actions = array(
        'delete' => array(
          'title' => t('Delete'),
          'href' => "admin/build/share/". $share->share_id ."/delete"
        ),
        'disable' => array(
          'title' => t('Disable'),
          'href' => "admin/build/share/". $share->share_id ."/disable"
        ),
        'enable' => array(
          'title' => t('Enable'),
          'href' => "admin/build/share/". $share->share_id ."/enable"
        ),
        'edit' => array(
          'title' => t('Edit'),
          'href' => "admin/build/share/". $share->share_id ."/edit"
        )
      );
      $row = array(
        array('data' => $share->label),
        array('data' => empty($types) ? '' : implode(', ', $types)),
        array(
          'data' => theme(
            'links',
            ($share->status) ? 
              array(
                $actions['edit'],
                $actions['disable']
              ) : array(
                $actions['edit'],
                $actions['enable'],
                $actions['delete']
              )
          )
        )
      );
      $rows[] = $row;
    }

    switch ($module) {
      case 'share':
        $title = t('Share');
        break;
      case 'sharethis_widget':
        $title = t('ShareThis');
        break;
      default:
        $title = $module;
        break;
    }
    $element = array(
      '#title' => $title,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#value' => theme('table', $headers, $rows)
    );
    $output.= theme('fieldset', $element);
  }

  return $output;
}
