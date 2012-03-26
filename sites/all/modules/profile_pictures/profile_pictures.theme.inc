<?php
/**
 * @file
 *
 * Theme and output formatting functions
 */

/**
 * Returns themed table with list of available tokens for using in image saving path
 */
function theme_profile_pictures_token_list() {
  $tokens = array_merge(token_get_list('user'), token_get_list('profile_picture'));

  $headers = array(t('Token'), t('Replacement value'));
  $rows = array();
  foreach ($tokens as $key => $category) {
    $rows[] = array(array('data' => drupal_ucfirst($key) . ' ' . t('tokens'), 'class' => 'region', 'colspan' => 2));
    foreach ($category as $token => $description) {
      $row = array();
      $row[] = '[' . $token . ']';
      $row[] = $description;
      $rows[] = $row;
    }
  }

  return theme('table', $headers, $rows, array('class' => 'description'));
}

/**
 * Returns themed image with ?349r34uf... in $url support
 * @args
 *   $url - path to url (may have ?q=... suffix)
 *   $alt
 *   $title
 *   $attributes - either null, either array of HTML attributes or name of ImageCache preset
 */
function theme_profile_picture($url, $alt = '', $title = '', $attributes = NULL) {
  if (empty($url)) return '';
  $filepath = explode('?', $url);
  if (module_exists('imagecache') && (gettype($attributes) == string) && !empty($attributes)) {
    $image = theme('imagecache', $attributes, $filepath[0], $alt, $title);
  }
  else {
    $image = theme('image', $filepath[0], $alt, $title, $attributes);
  }
  $image = preg_replace('?src="([^"]*)"?', 'src="$1?' . $filepath[1] . '"', $image);
  return $image;
}
