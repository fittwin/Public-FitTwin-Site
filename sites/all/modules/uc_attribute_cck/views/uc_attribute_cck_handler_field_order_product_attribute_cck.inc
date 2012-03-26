<?php
// $Id: uc_attribute_cck_handler_field_order_product_attribute_cck.inc,v 1.1 2010/10/11 15:21:53 trungonly Exp $

/**
 * @file
 * View field handler for Ubercart CCK Attribute Views module.
 *
 */


/**
 * Field handler to provide a human-readable version of the selected combination of attributes.
 */
class uc_attribute_cck_handler_field_order_product_attribute_cck extends views_handler_field {

  /**
   * Defines a few default options for the combination field.
   */
  function option_definition() {
    $options = parent::option_definition();

    $options['cck_attributes']['has_label'] = array(
      'default' => 1,
    );
    $options['cck_attributes']['display'] = array(
      'default' => 'default',
    );

    return $options;
  }

  /**
   * Form for field options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['cck_attributes'] = array(
      '#type' => 'fieldset',
      '#title' => t('CCK Attributes'),
    );

    $form['cck_attributes']['has_label'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include field Label'),
      '#default_value' => $this->options['cck_attributes']['has_label'],
      '#description' => t('Includes Label of CCK Attributes field before Options.'),
    );

    // Filter by field
    $form['cck_attributes']['filter']['#tree'] = TRUE;
    $form['cck_attributes']['filter']['filter_text'] = array(
      '#type' => 'checkbox',
      '#title' => t('Output only specific CCK Attribute field(s)'),
      '#description' => t('If checked, you can specify each CCK Attribute field to display. If not checked, all fields are shown.'),
      '#default_value' => $this->options['cck_attributes']['filter']['filter_text'],
    );

    $options = _uc_attribute_cck_views_fetch_fields();

    if (count($options)) {
      $groups = array('all' => t('<All>'));
      $form['cck_attributes']['filter']['fields'] = array(
        '#title' => t('Content types'),
        '#description' => t('Listed only fields in-use for Product CCK Attributes.'),
        '#type' => 'select',
        '#options' => array(),                          // overrided later next
        '#attributes' => array('class' => 'views-master-dependent'),
        '#process' => array('views_process_dependency'),
        '#dependency' => array(
          'edit-options-cck-attributes-filter-filter-text' => array(1),
        ),
      );

      // Group options first to simplify the DOM objects that Views
      // dependent JS will act upon.
      $grouped_options = array();
      foreach ($options as $key => $option) {
        $group = preg_replace('/[^a-z0-9]/', '-', drupal_strtolower($option['group']));
        $groups[$group] = $option['group'];
        $grouped_options[$group][$key] = $option;
      }

      foreach ($grouped_options as $group => $group_options) {
        $form['cck_attributes']['filter']['name'][$group . '_start'] = array(
          '#type' => 'markup',
          '#value' => '<div class="views-dependent-all views-dependent-' . $group . '">',
          '#process' => array('views_process_dependency'),
          '#dependency' => array(
            'edit-options-cck-attributes-filter-filter-text' => array(1),
          ),
        );
        foreach ($group_options as $key => $option) {
          $form['cck_attributes']['filter']['name'][$key] = array(
            '#type' => 'checkbox',
            '#title' => t('!group: !field', array('!group' => $option['group'], '!field' => $option['title'])),
            '#description' => $option['help'],
            '#default_value' => $this->options['cck_attributes']['filter']['name'][$key],
            '#return_value' => $key,
            '#process' => array('views_process_dependency'),
            '#dependency' => array(
              'edit-options-cck-attributes-filter-filter-text' => array(1),
            ),
          );
        }
        $form['cck_attributes']['filter']['name'][$group . '_end'] = array(
          '#type' => 'markup',
          '#value' => '</div>',
          '#process' => array('views_process_dependency'),
          '#dependency' => array(
            'edit-options-cck-attributes-filter-filter-text' => array(1),
          ),
        );
      }

      $form['cck_attributes']['filter']['fields']['#options'] = $groups;
    }
    else {
      // No CCK field available yet
      $form['cck_attributes']['filter']['filter_text']['#title'] .= ' - ' . t('There are no available CCK field to choose.');
      $form['cck_attributes']['filter']['filter_text']['#default_value'] = 0;
      $form['cck_attributes']['filter']['filter_text']['#disabled'] = TRUE;
    }

    $form['cck_attributes']['display'] = array(
      '#type' => 'radios',
      '#title' => t('Display fields'),
      '#default_value' => in_array($this->options['cck_attributes']['display'], array('default', 'teaser', 'full')) ? $this->options['cck_attributes']['display'] : 'default',
      '#description' => t('Select Display field options definition to apply. You can set specific <em>Display fields</em> format for each field in <em>Content type -> Manage fields</em> settings.'),
      '#options' => array(
        'default' => t('Default'),
        'teaser' => t('Teaser settings'),
        'full' => t('Full node settings'),
      ),
    );

  }

  /**
   * Called to add the field to a query.
   */
  function query() {
    parent::query();

    $fields = array(
      'field' => 'nid',                     // requires for getting CCK field info from node.
    );

    $this->add_additional_fields($fields);
  }

  function render(&$values) {



    $data = unserialize($values->{$this->field_alias});
    $nid = $values->uc_order_products_nid;  // TODO: we should check variable of this alias
    $node = node_load($nid);                // thanks for Drupal node_load() to have caching to save loading performance.

    $result = '';
    if (is_array($data['attributes_cck'])) {
      $rows = array();
      foreach ($data['attributes_cck'] as $field_name => $field) {

        if ($this->options['cck_attributes']['filter']['filter_text']) {                      // Check separate field
          if (!$this->options['cck_attributes']['filter']['name'][$field_name]) continue;
        }

        $attributes_cck = & $node->attributes_cck['fields'][$field_name];
        $display = in_array($this->options['cck_attributes']['display'], array('default', 'teaser', 'full')) ? $this->options['cck_attributes']['display'] : 'default';
        $display_settings = ($display == 'default') ? 'default' : ($attributes_cck['display_settings'][$display]['format'] ? $attributes_cck['display_settings'][$display]['format'] : 'default');
        $label = $attributes_cck['widget']['label'];                       // label
        $option = &content_format($field_name, $field[0], $display_settings);
        $rows[] = $this->options['cck_attributes']['has_label']
          ? t('@attribute: !option', array('@attribute' => $label, '!option' => $option))
          : $option;
      }
      if (count($rows)) {
        $result = theme('item_list', $rows, NULL, 'ul', array('class' => 'product-description'));
      }
    }
    return $result;
  }
}
