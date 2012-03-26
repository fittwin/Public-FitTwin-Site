<?php
// $Id: uc_attribute_cck_views.views.inc,v 1.1 2010/10/11 15:21:53 trungonly Exp $

/**
 * @file
 * Views 2 hooks and callback registries for Ubercart CCK Attribute.
 */

/**
 * Implements hook_views_handlers().
 */
function uc_attribute_cck_views_views_handlers() {
  return array(
    'info' => array(
      // nothing needed
    ),
    'handlers' => array(
      // fields
      'uc_attribute_cck_handler_field_order_product_attribute_cck' => array('parent' => 'views_handler_field'),
    ),
  );
}

/**
 * Implements hook_views_data().
 */
function uc_attribute_cck_views_views_data() {

  // Add viewhandler for uc_order_product attributes
  $data['uc_order_products']['attributes_cck'] = array(
    'title' => t('Product CCK Attributes'),
    'help' => t('List of CCK attribute selection for the ordered product.'),
    'group' => t('Order product'),
    'field' => array(
      'table' => 'uc_order_products',
      'field' => 'data',
      'handler' => 'uc_attribute_cck_handler_field_order_product_attribute_cck',
    ),
  );

  return $data;
}

