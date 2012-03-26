<?php
// $Id: uc_recurring.uc_authorizenet.inc,v 1.1.2.4 2010/12/08 23:05:27 univate Exp $

/**
 * @file
 * Uc recurring implementation for the test gateway module.
 */

/**
 * Implementation of hook_recurring_info().
 */
function uc_recurring_uc_authorizenet_recurring_info() {
  $items['authorizenet_cim'] = array(
    'name' => t('Authorize.net (CIM)'),
    'payment method' => 'credit',
    'module' => 'uc_recurring',
    'fee handler' => 'authorizenet_cim',
    'renew callback' => 'uc_recurring_authorizenet_cim_renew',
    'process callback' => 'uc_recurring_authorizenet_cim_process',
    'saved profile' => TRUE,
    'menu' => array(
      'charge' => UC_RECURRING_MENU_DEFAULT,
      'edit'   => UC_RECURRING_MENU_DEFAULT,
      'update' => array(
        'title' => 'Update Account Details',
        'page arguments' => array('uc_recurring_authorizenet_cim_update_form'),
        'file' => 'includes/uc_recurring.uc_authorizenet.inc',
      ),
      'profile' => array(
        'title' => 'CIM Profile',
        'page arguments' => array('uc_recurring_authorizenet_cim_profile_form'),
        'access callback' => 'user_access',
        'access arguments' => array('administer recurring fees'),
        'file' => 'includes/uc_recurring.uc_authorizenet.inc',
      ),
      'cancel' => UC_RECURRING_MENU_DEFAULT,
    ), // Use the default user operation defined in uc_recurring.
  );

  if (variable_get('uc_authnet_cim_mode', 'disabled') != 'disabled') {
    $items['authorizenet'] = $items['authorizenet_cim'];
  }
  return $items;
}

/**
 * Set up the recurring fee by creating a CIM profile for future payments
 *
 * @param $order
 *   The order object.
 * @param $fee
 *   The fee object.
 * @return
 *   TRUE if recurring fee setup
 */
function uc_recurring_authorizenet_cim_process($order, &$fee) {
  $fee->fee_handler = 'authorizenet_cim';
  if (variable_get('uc_authnet_cim_profile', FALSE) == FALSE) {
    $data = array(
      'txn_type' => UC_CREDIT_REFERENCE_SET,
    );
    return uc_authorizenet_charge($order->order_id, $order->total_amount, $data);
  }
  return TRUE;
}

/**
 * Process a renewal using the CIM profile.
 *
 * @param $order
 *   The order object.
 * @param $fee
 *   The fee object.
 * @return
 *   TRUE if renewal succeeded
 */
function uc_recurring_authorizenet_cim_renew($order, &$fee) {
  if (!empty($order->data['cc_txns']['references'])) {
    $data = array(
      'txn_type' => UC_CREDIT_REFERENCE_TXN,
      'ref_id' => end(array_keys($order->data['cc_txns']['references'])),
    );
    $result = uc_authorizenet_charge($order->order_id, $order->order_total, $data);

    if ($result['success'] == TRUE) {
      uc_payment_enter($order->order_id, $order->payment_method, $order->order_total, $fee->uid, $result['data'], $result['comment']);
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Create form for updating credit card details for recurring fee.
 */
function uc_recurring_authorizenet_cim_update_form($form_state, $rfid) {
  // Load fee.
  $fee = uc_recurring_fee_user_load($rfid);
  // Load corresponding order.
  $order = uc_order_load($fee->order_id);

  $form['rfid'] = array(
    '#type' => 'value',
    '#value' => $rfid,
  );
  $form['cc_data'] = array(
    '#type' => 'fieldset',
    '#title' => t('Credit card details'),
    '#theme' => 'uc_payment_method_credit_form',
    '#tree' => TRUE,
  );
  $form['cc_data'] += uc_payment_method_credit_form(array(), $order);
  unset($form['cc_data']['cc_policy']);

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#suffix' => l(t('Cancel'), 'user/'. $user->uid),
  );

  return $form;
}

/**
 * Implements update form submit for the authorizenet CIM gateway.
 */
function uc_recurring_authorizenet_cim_update_form_submit(&$form, &$form_state) {
  $fee = uc_recurring_fee_user_load($form_state['values']['rfid']);
  $order = uc_order_load($fee->order_id);
  $order->payment_details = $form_state['values']['cc_data'];
  $profile_id = end(array_keys($order->data['cc_txns']['references']));
  if ($message = _uc_recurring_uc_authorizenet_cim_update_paymentprofile($order, $profile_id)) {
    drupal_set_message('Account update failed.', 'error');
  }
  else {
    drupal_set_message('Account updated.');
    $form_state['redirect'] = 'user/'. $form_state['values']['uid'];
  }
}

/**
 * TODO: This function needs to be moved to the uc_authorizenet module and
 *       renamed to:
 *         uc_authorizenet_cim_update_paymentprofile($order, $ref_id)
 */
function _uc_recurring_uc_authorizenet_cim_update_paymentprofile($order, $ref_id) {
  $server = variable_get('uc_authnet_cim_mode', 'disabled');

  // Help build the request.
  $request = _uc_recurring_uc_authorizenet_cim_paymentprofile_update_request($order, $ref_id);

  // Request a profile from auth.net.
  $xml = _uc_authorizenet_xml_api_wrapper('updateCustomerPaymentProfileRequest', _uc_authorizenet_array_to_xml($request));

  // Parse the response.
  $response = _uc_authorizenet_cim_parse_response(uc_authorizenet_xml_api($server, $xml));
  if ($response['resultCode'] == 'Error') {
    uc_order_comment_save($order->order_id, 0, t('Authorize.Net: Update CIM profile failed.<br />@error - @text', array('@error' => $response['code'], '@text' => $response['text'])), 'admin');
    return $response['text'];
  }
  else {
    uc_order_comment_save($order->order_id, 0, t('Authorize.Net: CIM profile update - @id', array('@id' => $response['customerProfileId'])));
  }
}

/**
 * TODO: This function needs to be moved to the uc_authorizenet module and
 *       renamed to:
 *         _uc_authorizenet_cim_paymentprofile_update_request($order, $ref_id)
 */
function _uc_recurring_uc_authorizenet_cim_paymentprofile_update_request($order, $ref_id) {
  $profile = _uc_authorizenet_cim_profile_get($order, $ref_id);
  if ($profile['resultCode'] == 'Error') {
    return $profile;
  }
  else {
    $request = array(
      'refId' => substr($order->order_id .'-'. time(), 0, 20),
      'customerProfileId' => $profile['customerProfileId'],
      'paymentProfile' => array(
        'billTo' => _uc_authorize_cim_xml_billto($order),
        'payment' => array(
          'creditCard' => array(
            'cardNumber' => $order->payment_details['cc_number'],
            'expirationDate' => $order->payment_details['cc_exp_year'] .'-'. str_pad($order->payment_details['cc_exp_month'], 2, '0', STR_PAD_LEFT),
          ),
        ),
        'customerPaymentProfileId' => $profile['customerPaymentProfileId'],
      ),
    );
    return $request;
  }
}

/**
 * List the CIM profile information.
 */
function uc_recurring_authorizenet_cim_profile_form($form_state, $rfid) {
  $fee = uc_recurring_fee_user_load($rfid);
  $order = uc_order_load($fee->order_id);
  $ref_id = end(array_keys($order->data['cc_txns']['references']));
  $profile = _uc_authorizenet_cim_profile_get($order, $ref_id);

  $header = array_keys($profile);
  $rows[] = array_values($profile);

  $form['info'] = array(
    '#value' => t('The profile details below were return from Authorize.net'),
  );
  $form['profile'] = array(
    '#value' => theme('table', $header, $rows),
  );

  return $form;
}
