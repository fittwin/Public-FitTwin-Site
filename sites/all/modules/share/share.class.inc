<?php
// $Id: share.class.inc,v 1.1.2.5 2009/03/26 21:12:20 greenskin Exp $

class share {
  var $share_id;
  var $module = 'share';
  var $label;
  var $node_types = array();
  var $terms = array();
  var $type = 'link';
  var $view = 1;
  var $status = 1;

  function visible($node, $teaser, $page = TRUE) {
    $visible = FALSE;
    switch ($this->view) {
      case 0:
        if ($teaser && !$page) {
          $visible = TRUE;
        }
        break;
      case 1:
        if (!$teaser && $page) {
          $visible = TRUE;
        }
        break;
      case 2:
        if ($teaser || $page) {
          $visible = TRUE;
        }
        break;
    }
    if ($visible === TRUE) {
      if (!empty($this->node_types)) {
        $visible = FALSE;
        if (in_array($node->type, $this->node_types)) {
          $visible = TRUE;
        }
      }
      if ($visible === TRUE && !empty($this->terms)) {
        $visible = FALSE;
        $terms = taxonomy_node_get_terms($node);
        foreach ($this->terms as $tid) {
          if (isset($terms[$tid]) && !empty($terms[$tid])) {
            $visible = TRUE;
            break;
          }
        }
      }
    }
    return $visible;
  }

  function defaults() {
    $info = module_invoke($this->module, 'share_info');
    if (isset($info['widget']) && !empty($info['widget'])) {
      foreach ($info['widget'] as $key => $value) {
        $this->$key = $value;
      }
    }
  }

  function load($share_id) {
    $data = db_fetch_object(db_query("SELECT s.share_id, s.module, s.label, s.type, s.view, s.status, s.weight FROM {share} AS s WHERE s.share_id = %d", $share_id));

    foreach ($data as $key => $value) {
      $this->$key = $value;
    }

    $this->load_node_types();
    $this->load_terms();
  }

  function load_node_types() {
    $this->node_types = array();
    $result = db_query("SELECT node_type FROM {share_node_types} WHERE share_id = %d", $this->share_id);
    while ($data = db_fetch_object($result)) {
      $this->node_types[$data->node_type] = $data->node_type;
    }
  }

  function load_terms() {
    $this->terms = array();
    $result = db_query("SELECT tid FROM {share_terms} WHERE share_id = %d", $this->share_id);
    while ($data = db_fetch_object($result)) {
      $this->terms[$data->tid] = $data->tid;
    }
  }

  function render($node, $op = FALSE) {
    $output = '';
    return $output;
  }
}
