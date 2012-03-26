// $Id: share_widget_block.js,v 1.1.2.1 2009/03/26 21:12:23 greenskin Exp $

if (Drupal.jsEnabled) {
  $(document).ready(function() {
    $("div.share-block-tab-content").each(function(i) {
      $(this).hide();
    });
    $("div.share-block-tab h2").each(function(i) {
      var subject = $(this);
      var tab = $(subject).parent();
      var block = $(tab).parent();
      var content = $(tab).children('div.share-block-tab-content');
      subject.click(function() {
        if (content.css('display') == 'none') {
          block.children("div.share-block-tab").each(function(i) {
            var thisContent = $(this).children('div.share-block-tab-content');
            if (thisContent.css('display') != 'none') {
              thisContent.animate({
                'height': 'hide',
                'opacity': 'hide'
              });
            }
          });
          content.animate({
            'height': 'show',
            'opacity': 'show'
          });
        } else {
          content.animate({
            'height': 'hide',
            'opacity': 'hide'
          });
        }
      });
    });
  });
}
