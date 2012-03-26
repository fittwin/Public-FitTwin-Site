<?php
// $Id: template.php,v 1.21 2009/08/12 04:25:15 johnalbin Exp $

/**
 * @file
 * Contains theme override functions and preprocess functions for the theme.
 *
 * ABOUT THE TEMPLATE.PHP FILE
 *
 *   The template.php file is one of the most useful files when creating or
 *   modifying Drupal themes. You can add new regions for block content, modify
 *   or override Drupal's theme functions, intercept or make additional
 *   variables available to your theme, and create custom PHP logic. For more
 *   information, please visit the Theme Developer's Guide on Drupal.org:
 *   http://drupal.org/theme-guide
 *
 * OVERRIDING THEME FUNCTIONS
 *
 *   The Drupal theme system uses special theme functions to generate HTML
 *   output automatically. Often we wish to customize this HTML output. To do
 *   this, we have to override the theme function. You have to first find the
 *   theme function that generates the output, and then "catch" it and modify it
 *   here. The easiest way to do it is to copy the original function in its
 *   entirety and paste it here, changing the prefix from theme_ to fittwin_.
 *   For example:
 *
 *     original: theme_breadcrumb()
 *     theme override: fittwin_breadcrumb()
 *
 *   where fittwin is the name of your sub-theme. For example, the
 *   zen_classic theme would define a zen_classic_breadcrumb() function.
 *
 *   If you would like to override any of the theme functions used in Zen core,
 *   you should first look at how Zen core implements those functions:
 *     theme_breadcrumbs()      in zen/template.php
 *     theme_menu_item_link()   in zen/template.php
 *     theme_menu_local_tasks() in zen/template.php
 *
 *   For more information, please visit the Theme Developer's Guide on
 *   Drupal.org: http://drupal.org/node/173880
 *
 * CREATE OR MODIFY VARIABLES FOR YOUR THEME
 *
 *   Each tpl.php template file has several variables which hold various pieces
 *   of content. You can modify those variables (or add new ones) before they
 *   are used in the template files by using preprocess functions.
 *
 *   This makes THEME_preprocess_HOOK() functions the most powerful functions
 *   available to themers.
 *
 *   It works by having one preprocess function for each template file or its
 *   derivatives (called template suggestions). For example:
 *     THEME_preprocess_page    alters the variables for page.tpl.php
 *     THEME_preprocess_node    alters the variables for node.tpl.php or
 *                              for node-forum.tpl.php
 *     THEME_preprocess_comment alters the variables for comment.tpl.php
 *     THEME_preprocess_block   alters the variables for block.tpl.php
 *
 *   For more information on preprocess functions and template suggestions,
 *   please visit the Theme Developer's Guide on Drupal.org:
 *   http://drupal.org/node/223440
 *   and http://drupal.org/node/190815#template-suggestions
 */



/**
 * Implementation of HOOK_theme().
 */
function fittwin_theme(&$existing, $type, $theme, $path) {
  $hooks = zen_theme($existing, $type, $theme, $path);
  // Add your theme hooks like this:
  /*
  $hooks['hook_name_here'] = array( // Details go here );
  */
  // @TODO: Needs detailed comments. Patches welcome!
  return $hooks;
}

/**
 * Override or insert variables into all templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered (name of the .tpl.php file.)
 */
function fittwin_preprocess(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
  //dsm(&$vars['view']);
}
// */

/**
 * Override or insert variables into the page templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("page" in this case.)
 */
/* -- Delete this line if you want to use this function
function fittwin_preprocess_page(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */

/**
 * Override or insert variables into the node templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("node" in this case.)
 */
/* -- Delete this line if you want to use this function
function fittwin_preprocess_node(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');

  // Optionally, run node-type-specific preprocess functions, like
  // fittwin_preprocess_node_page() or fittwin_preprocess_node_story().
  $function = __FUNCTION__ . '_' . $vars['node']->type;
  if (function_exists($function)) {
    $function($vars, $hook);
  }
}
// */

/**
 * Override or insert variables into the comment templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("comment" in this case.)
 */
/* -- Delete this line if you want to use this function
function fittwin_preprocess_comment(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */

/**
 * Override or insert variables into the block templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("block" in this case.)
 */
/* -- Delete this line if you want to use this function
function fittwin_preprocess_block(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */


/**
 * Process variables for search-theme-form.tpl.php.
 *
 * @param $vars
 * 	A sequential array of variables to pass to the theme template.
 * @param $hook
 * 	The name of the theme function being called (not used in this case).
 *
 * @see search-theme-form.tpl.php
 */
function fittwin_preprocess_search_theme_form(&$vars, $hook) {
// Modify elements of the search form
  $vars['form']['search_theme_form']['#title'] = t('Search FitTwin.com');
  
  // Set a default value for the search box
  $vars['form']['search_theme_form']['#value'] = t('Search');
  
  // Add a custom class to the search box
  $vars['form']['search_theme_form']['#attributes'] = array('class' => t('cleardefault'));
  
  // Change the text on the submit button
  $vars['form']['submit']['#value'] = t('Go');
  
  // Rebuild the rendered version (search form only, rest remains unchanged)
  unset($vars['form']['search_theme_form']['#printed']);
  $vars['search']['search_theme_form'] = drupal_render($vars['form']['search_theme_form']);
  
  // Rebuild the rendered version (submit button, rest remains unchanged)
  unset($vars['form']['submit']['#printed']);
  $vars['search']['submit'] = drupal_render($vars['form']['submit']);
  // Collect all form elements to make it easier to print the whole form.
  $vars['search_form'] = implode($vars['search']);
}

/**
 * Process variables for search-block-form.tpl.php.
 *
 * @param $vars
 * 	A sequential array of variables to pass to the theme template.
 * @param $hook
 * 	The name of the theme function being called (not used in this case).
 *
 * @see search-block-form.tpl.php
 */
function fittwin_preprocess_search_block_form(&$vars, $hook) {
  // Modify elements of the search form
  $vars['form']['search_theme_form']['#title'] = t('');
  
  // Set a default value for the search box
  $vars['form']['search_theme_form']['#value'] = t('');
  
  // Add a custom class to the search box
  
  // Change the text on the submit button
  $vars['form']['submit']['#value'] = t('Search FitTwin.com');
  $vars['form']['submit']['#type'] = 'image_button';
  $vars['form']['submit']['#src'] = 'sites/all/themes/fittwin//images/search-button.jpg';
  
  // Rebuild the rendered version (search form only, rest remains unchanged)
  unset($vars['form']['search_theme_form']['#printed']);
  $vars['search']['search_theme_form'] = drupal_render($vars['form']['search_theme_form']);
  
  // Rebuild the rendered version (submit button, rest remains unchanged)
  unset($vars['form']['submit']['#printed']);
  $vars['search']['submit'] = drupal_render($vars['form']['submit']);
  // Collect all form elements to make it easier to print the whole form.
  $vars['search_form'] = implode($vars['search']);
}

function mymodule_form_alter(&$form, &$form_state, $form_id) {
    if (preg_match("/uc-product-add-to-cart-form/", $form_id)) {
        $form['#action'] = url('node/' . $form['nid']['#value']);
    }
}

/**
 * Implements theme_menu_item_link()
 */
function fittwin_menu_item_link($link) {
  if (empty($link['localized_options'])) {
    $link['localized_options'] = array();
  }

  // If an item is a LOCAL TASK, render it as a tab
  if ($link['type'] & MENU_IS_LOCAL_TASK) {
    $link['title'] = '<span class="tab">' . check_plain($link['title']) . '</span>';
    $link['localized_options']['html'] = TRUE;
  }

  return l($link['title'], $link['href'], $link['localized_options']);
}


/*function fittwin_button($element) {
  // Make sure not to overwrite classes.
  if (isset($element['#attributes']['class'])) {
    $element['#attributes']['class'] = 'form-' . $element['#button_type'] . ' ' . $element['#attributes']['class'];
  }
  else {
    $element['#attributes']['class'] = 'form-' . $element['#button_type'];
  }
  $element['#attributes']['src'] = 'sites/all/themes/fittwin/images/bkg_button.png';

  return '<input type="submit" ' . (empty($element['#name']) ? '' : 'name="' . $element['#name'] . '" ') . 'id="' . $element['#id'] . '" value="' . check_plain($element['#value']) . '" ' . drupal_attributes($element['#attributes']) . " />\n";
}
*/


/*function fittwin_preprocess_views_exposed_form(&$vars) {
  $form = $vars['form'];
  if ($form['#id'] == 'views-exposed-form-facility-proximity-view-page-1') {
  print('<br><br><br><br>');
    print '<pre>';
    var_dump($vars['form_state']);
      print '</pre>';
 // print_r($form);
    //alter the $form array here
    $form['submit']['#value'] = t('Find an Offer Near You');
    unset($form['submit']['#printed']);
    $vars['button'] = drupal_render($form['submit']);

    $form['distance']['postal_code']['#value'] = 27606;
    unset($form['distance']['postal_code']['#printed']);
    $vars['form'] = drupal_render($form);

  }
}*/
