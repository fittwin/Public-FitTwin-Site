<?php
// $Id: share_widget.class.inc,v 1.1.2.1 2009/03/26 21:12:22 greenskin Exp $

class share_widget extends share {
  var $module = 'share_widget';
  var $tabs = array();

  function visible($node, $teaser, $page = TRUE) {
    return (user_access('use share widget') && parent::visible($node, $teaser, $page));
  }

  function load($share_id) {
    $data = db_fetch_object(db_query("SELECT s.share_id, s.module, s.label, s.type, s.view, s.status, s.weight, sp.name, sp.effects
      FROM {share} AS s LEFT JOIN {share_widget} AS sp ON s.share_id = sp.share_id WHERE s.share_id = %d", $share_id));

    foreach ($data as $key => $value) {
      $this->$key = $value;
    }

    $this->load_node_types();
    $this->load_terms();
    $this->load_tabs();
  }

  function load_tabs() {
    $tabs = share_widget_default_tabs();

    $result = db_query("SELECT title, enabled, weight, module FROM {share_tabs} WHERE share_id = %d ORDER BY weight, title", $this->share_id);
    while ($tab = db_fetch_array($result)) {
      $args = drupal_clone($this);
      $tab_data = module_invoke($tab['module'], 'share_tab', 'load', $args);
      if (!empty($tab_data) && is_array($tab_data)) {
        $tab = $tab + $tab_data;
      }
      $tabs[$tab['module']] = $tab;
    }

    // Sort tabs by weight and then by title.
    uasort($tabs, 'share_widget_tab_sort');

    $this->tabs = $tabs;
  }

  function render($node, $op = FALSE) {
    $output = '';
    switch ($op) {
      case 'link':
        $output .= l($this->name, 'share/'. $this->share_id .'/'. $node->nid, array('attributes' => array('id' => 'share-'. $this->share_id .'-'. $node->nid, 'class' => 'share-link')));
        break;
      case 'content':
        $output .= $this->render_content($node);
        break;
      default:
        $output .= $this->render($node, 'link');
        $output .= $this->render($node, 'content');
        break;
    }
    return $output;
  }

  function render_tab($module, $node) {
    $tab = $this->tabs[$module];
    return module_invoke($tab['module'], 'share_tab', 'process', $tab, $node);
  }

  function render_content($node) {
    $tabs = array();
    $content = array();
    foreach ($this->tabs as $tab) {
      if (!$tab['enabled']) {
        continue;
      }

      if ($result = $this->render_tab($tab['module'], $node)) {
        $tabs[] = array(
          'module' => str_replace('_', '-', check_plain($tab['module'])),
          'name' => $tab['title']
        );

        $content[] = array(
          'module' => str_replace('_', '-', check_plain($tab['module'])),
          'content' => $result
        );
      }
    }

    $data['tabs'] = theme('share_widget_tabs', $tabs, $node->nid);
    $data['content'] = theme('share_widget_content', $content, $node->nid);
    return theme('share_widget', $data);
  }
}
