# $Id: README.txt,v 1.1.4.2 2009/03/26 21:12:20 greenskin Exp $

Currently to allow the use of a Forward tab and a Service Links tab, you will need to apply the included patches to the module. In addition for the Service Links you will need to copy the service_links.css file into the actual service_links directory inside your modules folder. The Service Links patch was created on the 5.x-1.1 version. The Forward patch was created using the 5.x-1.14 version.

To allow your module to provide a tab in the Share widget, you will need to implement the following two hooks:

hook_share_info()
-------------
/**
 * This makes your tab known to Share.
 *
 * @return
 *   Return an array with the elements 'id' => (the id for your tab),
 *   'title' => (the suggested title for your tab),
 *   'enabled' => (TRUE/FALSE, whether or not the tab is enabled by default),
 *   and 'weight' => (the default weight of the tab).
 */
function example_share_info() {
  $info = array(
    'id' => 'example',
    'title' => t('Example tab'),
    'enabled' => TRUE,
    'weight' => 0
  );
  return $info;
}


hook_share_tab()
-------------
/**
 * This hook performs necessary actions for your tab.
 *
 * @param $op
 *   The action to perform. Possible actions include 'load', 'settings',
 *   'validate', 'insert', 'update', and 'process'.
 * @param $args
 *   The share object for all actions except for 'settings' and 'process' then
 *   it is the only module specific tab information.
 * @param $node
 *   The node object if available.
 *
 * @return
 *   The API Key.
 */
function example_share_tab($op, $args, $node = NULL) {
  switch ($op) {
    case 'load':
      if ($field = db_result(db_query("SELECT field_setting FROM {share_example} WHERE share_id = %d", $args->share_id))) {
        return array('field_setting' => $field);
      }
      break;
    case 'settings':
      $form['field_setting'] = array(
        '#type' => 'textfield',
        '#title' => t('Field'),
        '#default_value' => $args->field_setting,
      );
      return $form;
    case 'validate':
      if (empty($args['field_setting'])) {
        form_set_error('field_setting', t('At least one link code type has to be enabled.'));
      }
      break;
    case 'insert':
      db_query("INSERT INTO {share_example} (share_id, field_setting) VALUES (%d, '%s')",
        $args['share_id'], $args['field_setting']);
      break;
    case 'update':
      db_query("UPDATE {share_example} SET field_setting = '%s' WHERE share_id = %d",
        $args['field_setting'], $args['share_id']);
      break;
    case 'process':
      $output = $args->field_setting;
      return $output;
  }
}
