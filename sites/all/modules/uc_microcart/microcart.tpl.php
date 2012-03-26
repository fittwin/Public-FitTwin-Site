<?php
$label = ($cart_metadata['count'] == 1)? 'item':'items';
$img = theme_image(drupal_get_path('module', 'uc_microcart') . '/images/cart1.gif');
$txt  = sprintf('%d %s %s', $cart_metadata['count'], $label, uc_currency_format($cart_metadata['total']));

echo l($img . $txt, 'cart', array('attributes'=>array('title'=>t('View your cart.')), 'html' => true));
