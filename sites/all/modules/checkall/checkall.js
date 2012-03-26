// $Id: checkall.js,v 1.2.2.9 2009/08/06 07:28:55 markuspetrux Exp $

Drupal.behaviors.checkAll = function(context) {
  $('.form-checkall:not(.checkall-processed)', context).each(function() { Drupal.checkAll.attach(this); });
};

Drupal.checkAll = Drupal.checkAll || {
  strings: {
    checkAll: Drupal.t('Check all items in this group'),
    checkToggle: Drupal.t('Toggle the values of all items in this group'),
    checkNone: Drupal.t('Uncheck all items in this group')
  }
};

Drupal.checkAll.attach = function(checkBoxes) {
  var groupClass = '';

  // Find the class that connects the checkboxes group with its individual checkbox elements.
  if (Drupal.settings.checkall && Drupal.settings.checkall.groups) {
    // If there's just one group, then it comes in the form of a string.
    // So in this case, we need to convert the setting into an array.
    if (typeof Drupal.settings.checkall.groups == 'string') {
      Drupal.settings.checkall.groups = [Drupal.settings.checkall.groups];
    }
    var groupClasses = checkBoxes.className.toString().split(/\s+/);
    for (var i = 0; i < groupClasses.length; i++) {
      for (var j = 0; j < Drupal.settings.checkall.groups.length; j++) {
        if (groupClasses[i] == Drupal.settings.checkall.groups[j]) {
          groupClass = groupClasses[i];
          break;
        }
      }
      if (groupClass.length > 0) {
        break;
      }
    }
  }

  // Make sure this behavior is not processed more than once.
  $(checkBoxes).addClass('checkall-processed');

  if (groupClass.length > 0 && $('th.'+ groupClass).size() > 0) {
    // The behavior is attached to all checkbox items that share the
    // same CSS class that was used in the '#checkall' property of
    // the FormAPI checkboxes element.
    Drupal.checkAll.attachByClass(groupClass);
  }
  else {
    // The behavior is attached to all checkbox items that are
    // children of the checkboxes wrapper.
    Drupal.checkAll.attachByGroup(checkBoxes);
  }
};

Drupal.checkAll.attachByClass = function(groupClass) {
  // Adjust the state and the title attribute of header checkbox depending
  // on whether all checkboxes in the same group are checked or not.
  function setHeaderStatus() {
    if ($('input:checkbox').filter('.'+ groupClass).size() == $('input:checkbox').filter('.'+ groupClass).filter(':checked').size()) {
      $('th.'+ groupClass +' input:checkbox').filter(':not(:checked)').attr({'checked': true, 'title': Drupal.checkAll.strings.checkNone}).trigger('change');
    }
    else {
      $('th.'+ groupClass +' input:checkbox').filter(':checked').attr({'checked': false, 'title': Drupal.checkAll.strings.checkAll}).trigger('change');
    }
  }

  // Attach a check/uncheck checkbox to table header cell.
  $('th.'+ groupClass).append($('<div></div>')).append(
    $('<input type="checkbox" class="form-checkbox" />').attr('title', Drupal.checkAll.strings.checkAll).click(function(event) {
      var state = event.target.checked;
      $(this).attr('title', state ? Drupal.checkAll.strings.checkNone : Drupal.checkAll.strings.checkAll);
      $('input:checkbox').filter('.'+ groupClass).filter(state ? ':not(:checked)' : ':checked').attr('checked', state).trigger('change');
    })
  );

  // Attach a click event to each checkbox so that we can re-evaluate the
  // state of the header checkbox when all items are checked/unchecked.
  $('input:checkbox').filter('.'+ groupClass).click(function() {
    setHeaderStatus();
  });

  // Adjust header checkbox when the behavior is attached.
  setHeaderStatus();
};

Drupal.checkAll.attachByGroup = function(checkBoxes) {
  $(checkBoxes).prepend('<div class="checkall-action"></div>').children('.checkall-action')
    .prepend($('<a href="#">'+ Drupal.t('Uncheck all') +'</a>').attr('title', Drupal.checkAll.strings.checkNone).click(function() {
      $('input:checkbox', checkBoxes).attr('checked', false).trigger('change');
      return false;
    }))
    .prepend($('<span> / </span>'))
    .prepend($('<a href="#">'+ Drupal.t('Toggle') +'</a>').attr('title', Drupal.checkAll.strings.checkToggle).click(function() {     
      var $checkedBoxes = $('input:checked', checkBoxes);
      $('input:not(:checked)', checkBoxes).attr('checked', true).trigger('change');
      $($checkedBoxes).attr('checked', false).trigger('change');
      return false;
    }))
    .prepend($('<span> / </span>'))
    .prepend($('<a href="#">'+ Drupal.t('Check all') +'</a>').attr('title', Drupal.checkAll.strings.checkAll).click(function() {
      $('input:checkbox', checkBoxes).attr('checked', true).trigger('change');
      return false;
    }));
};
