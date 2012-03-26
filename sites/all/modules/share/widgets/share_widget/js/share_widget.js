// $Id: share_widget.js,v 1.1.2.1 2009/03/26 21:12:23 greenskin Exp $

Drupal.SharePlugin = Drupal.SharePlugin || {};
Drupal.SharePlugin.links = new Array();

Drupal.behaviors.share = function() {
  if (typeof Drupal.SharePlugin.migrate == 'function') {
    Drupal.SharePlugin.migrate();
  }
  else {
    Drupal.SharePlugin.init();
  }
}

Drupal.SharePlugin.init = function() {
  $("a.share-link").each(function(i) {
    var id = $(this).attr('id');
    Drupal.SharePlugin.links[id] = new Drupal.SharePlugin.share(this);
  });
}

Drupal.SharePlugin.share = function(object) {
  var self = this;

  this.share = $(object).parent();
  this.link = $(object);
  this.widget = $("div.share-widget", this.share);
  this.header = $("div.share-header", this.widget);
  this.menu = $("ul.share-menu li a", this.header);
  this.content = $("div.share-content", this.widget);

  // Set click if widget exists.
  if (this.widget.html()) {
    // Open/Close Share widget.
    this.link.unbind('click');
    this.link.click(function() {
      self.widget.animate({
        'height': 'toggle'
      }, 'fast');
      return false;
    });

    // Close Share widget.
    this.header.children("a.share-close").unbind('click');
    this.header.children("a.share-close").click(function() {
      self.widget.animate({
        'height': 'hide'
      }, 'fast');
      return false;
    });

    $.each(this.menu, function(j, n) {
      $(n).unbind('click');
      $(n).click(function() {
        var tabContent = 'div.' + $(this).parent().attr('class');
        if ($(tabContent, self.widget).css('display') == 'none') {
          $.each(self.menu, function(k, o) {
            var otherTabContent = 'div.'+ $(o).parent().attr('class');
            if ($(otherTabContent, self.widget).css('display') != 'none') {
              $(otherTabContent, self.widget).animate({
                'height': 'hide',
                'opacity': 'hide'
              });
              $(o).toggleClass('selected');
            }
          });
          $(this).toggleClass('selected');
          $(tabContent, self.widget).animate({
            'height': 'show',
            'opacity': 'show'
          });
        }
        return false;
      });
    });
  }
}
