<?php
/**
 * @file
 *   Profile picture field handler (views)
 */

class profile_pictures_field extends views_handler_field {
  function construct() {
    $this->additional_fields['uid'] =
       array(
         'table' => 'users',
         'field' => 'uid',
       );
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['max_height'] = array('default' => 0);
    $options['max_width'] = array('default' => 0);
    $options['imagecache_preset'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    
    $form['max_dims'] = array(
      '#prefix' => '<div class="profile_pictures_max_dims">',
      '#suffix' => '</div>',
      '#weight' => 10,
      'max_height' => array(
        '#type' => 'textfield',
        '#title' => t('Maximum height (0 for no limit)'),
        '#default_value' => $this->options['max_height'],
      ),
      'max_width' => array(
        '#type' => 'textfield',
        '#title' => t('Maximum width (0 for no limit)'),
        '#default_value' => $this->options['max_width'],
      ),
    );
    if (module_exists('imagecache')) {
      $js = array('module' => array(
        'sites/all/modules/profile_pictures/profile_pictures.js' => array(
          'cache' => TRUE,
          'defer' => FALSE,
          'preprocess' => TRUE, 
        ),
      ));
      $js = drupal_get_js('footer', $js);
      $form['js'] = array('#value' => $js);
      
      $presets = array();
      $presets[''] = t('--- Use maximum dimensions settings ---');
      foreach (imagecache_presets() as $preset) {
        $presets[$preset['presetname']] = t($preset['presetname']);
      }
      $form['imagecache_preset'] = array(
        '#type' => 'select',
        '#weight' => 9,
        '#attributes' => array(
          'class' => 'profile_pictures_imagecache_preset',
        ),
        '#title' => t('ImageCache preset'),
        '#default_value' => $this->options['imagecache_preset'],
        '#options' => $presets,
        '#description' => t('Please, use only imagecache preset or maximum dimensions settings'),
      );
    }
  }
  function options_validate(&$form, &$form_state) {
    if (isset($form['max_width']) && isset($form['max_height'])) {
      $v = $form_state['values']['options']['max_height'];
      if (!is_numeric($v) || ($v < 0))
        form_set_error('max_height', t('Maximum height must be a non-negative integer'));

      $v = $form_state['values']['options']['max_width'];
      if (!is_numeric($v) || ($v < 0))
        form_set_error('max_width', t('Maximum width must be non-negative integer'));  
    }
  }

  function render($values) {
    preg_match('/profile_values_(.*)/', $this->table, $fieldname);
    if (function_exists('profile_privacy_get_user_field_privacy')) {
      if (profile_privacy_get_user_field_privacy(
          $values->{$this->aliases['uid']}, $fieldname[1])) {
        return '';
      }
    }
    drupal_add_css(drupal_get_path('module', 'profile_pictures') . '/profile_pictures.css', 'module');
    
    
    if (module_exists('imagecache') && !empty($this->options['imagecache_preset'])) {
      $opts = $this->options['imagecache_preset'];
    }
    else {
      if ($this->options['max_height'] > 0) {
        $maxh = (int)$this->options['max_height'];
      }
      if ($this->options['max_width'] > 0) {
        $maxw = (int)$this->options['max_width'];
      }
  
      $filename = explode('?', $values->{$this->field_alias});
      $filename = $filename[0];
      // Calculating output image size (given maximum dimensions).
      // We need to calculate both width and height to output its
      // like CSS attributes.
      list($width, $height) = @getimagesize($filename);
      if ($maxh && ($height > $maxh)) {
        $width *= $maxh / $height;
        $height = $maxh;
      }
      if ($maxw && ($width > $maxw)) {
        $height *= $maxw / $width;
        $width = $maxw;
      }
      $opts = array(
        'class' => 'profile_pictures_field',
        'style' => 'width:' . $width . 'px; height:' . $height . 'px;',
      );
    }
    return theme('profile_picture', $values->{$this->field_alias}, $this->options['label'], '',
      $opts);
  }
}
