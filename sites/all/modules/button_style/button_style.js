// $Id: button_style.js,v 1.2 2009/08/14 18:46:42 sun Exp $

Drupal.behaviors.buttonStyle = function (context) {
  $('span.form-button-wrapper input, li.button a, a.button').mousedown(function () {
    $(this).blur();
    return true;
  });
}

